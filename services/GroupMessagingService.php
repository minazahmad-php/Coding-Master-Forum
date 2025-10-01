<?php
declare(strict_types=1);

namespace Services;

class GroupMessagingService {
    private Database $db;
    private EncryptionService $encryption;
    
    public function __construct() {
        $this->db = Database::getInstance();
        $this->encryption = new EncryptionService();
    }
    
    public function createGroup(string $name, string $description, array $members, bool $isPrivate = true): array {
        try {
            $this->db->beginTransaction();
            
            // Create group
            $groupId = $this->db->insert('message_groups', [
                'name' => $name,
                'description' => $description,
                'is_private' => $isPrivate ? 1 : 0,
                'created_by' => Auth::getUserId(),
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ]);
            
            // Add creator as admin
            $this->addGroupMember($groupId, Auth::getUserId(), 'admin');
            
            // Add members
            foreach ($members as $memberId) {
                if ($memberId != Auth::getUserId()) {
                    $this->addGroupMember($groupId, $memberId, 'member');
                }
            }
            
            $this->db->commit();
            
            return [
                'success' => true,
                'group_id' => $groupId,
                'message' => 'Group created successfully'
            ];
            
        } catch (\Exception $e) {
            $this->db->rollback();
            return [
                'success' => false,
                'message' => 'Failed to create group: ' . $e->getMessage()
            ];
        }
    }
    
    public function addGroupMember(int $groupId, int $userId, string $role = 'member'): bool {
        $validRoles = ['admin', 'moderator', 'member'];
        if (!in_array($role, $validRoles)) {
            return false;
        }
        
        // Check if user is already a member
        if ($this->isGroupMember($groupId, $userId)) {
            return false;
        }
        
        try {
            $this->db->insert('group_members', [
                'group_id' => $groupId,
                'user_id' => $userId,
                'role' => $role,
                'joined_at' => date('Y-m-d H:i:s')
            ]);
            
            // Send notification to user
            $this->sendGroupInviteNotification($groupId, $userId);
            
            return true;
        } catch (\Exception $e) {
            error_log("Error adding group member: " . $e->getMessage());
            return false;
        }
    }
    
    public function removeGroupMember(int $groupId, int $userId): bool {
        try {
            $this->db->delete(
                'group_members',
                'group_id = :group_id AND user_id = :user_id',
                ['group_id' => $groupId, 'user_id' => $userId]
            );
            
            // Send notification
            $this->sendGroupLeaveNotification($groupId, $userId);
            
            return true;
        } catch (\Exception $e) {
            error_log("Error removing group member: " . $e->getMessage());
            return false;
        }
    }
    
    public function updateGroupMemberRole(int $groupId, int $userId, string $role): bool {
        $validRoles = ['admin', 'moderator', 'member'];
        if (!in_array($role, $validRoles)) {
            return false;
        }
        
        try {
            $this->db->update(
                'group_members',
                ['role' => $role],
                'group_id = :group_id AND user_id = :user_id',
                ['group_id' => $groupId, 'user_id' => $userId]
            );
            
            return true;
        } catch (\Exception $e) {
            error_log("Error updating group member role: " . $e->getMessage());
            return false;
        }
    }
    
    public function sendGroupMessage(int $groupId, string $content, array $attachments = []): array {
        if (!$this->isGroupMember($groupId, Auth::getUserId())) {
            return [
                'success' => false,
                'message' => 'You are not a member of this group'
            ];
        }
        
        try {
            $this->db->beginTransaction();
            
            // Encrypt message content
            $encryptedContent = $this->encryption->encrypt($content);
            
            // Create message
            $messageId = $this->db->insert('group_messages', [
                'group_id' => $groupId,
                'user_id' => Auth::getUserId(),
                'content' => $encryptedContent,
                'message_type' => 'text',
                'created_at' => date('Y-m-d H:i:s')
            ]);
            
            // Handle attachments
            if (!empty($attachments)) {
                foreach ($attachments as $attachment) {
                    $this->addMessageAttachment($messageId, $attachment);
                }
            }
            
            // Update group last activity
            $this->db->update(
                'message_groups',
                ['updated_at' => date('Y-m-d H:i:s')],
                'id = :id',
                ['id' => $groupId]
            );
            
            // Send notifications to group members
            $this->sendGroupMessageNotifications($groupId, $messageId);
            
            $this->db->commit();
            
            return [
                'success' => true,
                'message_id' => $messageId,
                'message' => 'Message sent successfully'
            ];
            
        } catch (\Exception $e) {
            $this->db->rollback();
            return [
                'success' => false,
                'message' => 'Failed to send message: ' . $e->getMessage()
            ];
        }
    }
    
    public function getGroupMessages(int $groupId, int $page = 1, int $perPage = 50): array {
        if (!$this->isGroupMember($groupId, Auth::getUserId())) {
            return [];
        }
        
        $offset = ($page - 1) * $perPage;
        
        $messages = $this->db->fetchAll(
            "SELECT gm.*, u.username, u.avatar, u.online_status,
                    GROUP_CONCAT(ma.filename) as attachments
             FROM group_messages gm
             JOIN users u ON gm.user_id = u.id
             LEFT JOIN message_attachments ma ON gm.id = ma.message_id
             WHERE gm.group_id = :group_id
             ORDER BY gm.created_at DESC
             LIMIT :offset, :per_page",
            [
                'group_id' => $groupId,
                'offset' => $offset,
                'per_page' => $perPage
            ]
        );
        
        // Decrypt messages
        foreach ($messages as &$message) {
            $message['content'] = $this->encryption->decrypt($message['content']);
            $message['attachments'] = $message['attachments'] ? explode(',', $message['attachments']) : [];
        }
        
        return array_reverse($messages);
    }
    
    public function getUserGroups(int $userId = null): array {
        $userId = $userId ?? Auth::getUserId();
        
        return $this->db->fetchAll(
            "SELECT mg.*, gm.role, gm.joined_at,
                    (SELECT COUNT(*) FROM group_members WHERE group_id = mg.id) as member_count,
                    (SELECT content FROM group_messages WHERE group_id = mg.id ORDER BY created_at DESC LIMIT 1) as last_message,
                    (SELECT created_at FROM group_messages WHERE group_id = mg.id ORDER BY created_at DESC LIMIT 1) as last_message_time
             FROM message_groups mg
             JOIN group_members gm ON mg.id = gm.group_id
             WHERE gm.user_id = :user_id
             ORDER BY mg.updated_at DESC",
            ['user_id' => $userId]
        );
    }
    
    public function getGroupMembers(int $groupId): array {
        return $this->db->fetchAll(
            "SELECT u.id, u.username, u.avatar, u.online_status, gm.role, gm.joined_at
             FROM group_members gm
             JOIN users u ON gm.user_id = u.id
             WHERE gm.group_id = :group_id
             ORDER BY gm.role DESC, gm.joined_at ASC",
            ['group_id' => $groupId]
        );
    }
    
    public function isGroupMember(int $groupId, int $userId): bool {
        return $this->db->exists(
            'group_members',
            'group_id = :group_id AND user_id = :user_id',
            ['group_id' => $groupId, 'user_id' => $userId]
        );
    }
    
    public function isGroupAdmin(int $groupId, int $userId): bool {
        $member = $this->db->fetch(
            "SELECT role FROM group_members WHERE group_id = :group_id AND user_id = :user_id",
            ['group_id' => $groupId, 'user_id' => $userId]
        );
        
        return $member && $member['role'] === 'admin';
    }
    
    public function updateGroup(int $groupId, array $data): bool {
        if (!$this->isGroupAdmin($groupId, Auth::getUserId())) {
            return false;
        }
        
        $allowedFields = ['name', 'description', 'is_private'];
        $updateData = [];
        
        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $updateData[$field] = $data[$field];
            }
        }
        
        if (empty($updateData)) {
            return false;
        }
        
        $updateData['updated_at'] = date('Y-m-d H:i:s');
        
        try {
            $this->db->update(
                'message_groups',
                $updateData,
                'id = :id',
                ['id' => $groupId]
            );
            
            return true;
        } catch (\Exception $e) {
            error_log("Error updating group: " . $e->getMessage());
            return false;
        }
    }
    
    public function deleteGroup(int $groupId): bool {
        if (!$this->isGroupAdmin($groupId, Auth::getUserId())) {
            return false;
        }
        
        try {
            $this->db->beginTransaction();
            
            // Delete group messages
            $this->db->delete('group_messages', 'group_id = :group_id', ['group_id' => $groupId]);
            
            // Delete group members
            $this->db->delete('group_members', 'group_id = :group_id', ['group_id' => $groupId]);
            
            // Delete group
            $this->db->delete('message_groups', 'id = :id', ['id' => $groupId]);
            
            $this->db->commit();
            
            return true;
        } catch (\Exception $e) {
            $this->db->rollback();
            error_log("Error deleting group: " . $e->getMessage());
            return false;
        }
    }
    
    public function leaveGroup(int $groupId): bool {
        $userId = Auth::getUserId();
        
        // Check if user is the only admin
        $adminCount = $this->db->fetchColumn(
            "SELECT COUNT(*) FROM group_members WHERE group_id = :group_id AND role = 'admin'",
            ['group_id' => $groupId]
        );
        
        $userRole = $this->db->fetchColumn(
            "SELECT role FROM group_members WHERE group_id = :group_id AND user_id = :user_id",
            ['group_id' => $groupId, 'user_id' => $userId]
        );
        
        if ($userRole === 'admin' && $adminCount <= 1) {
            // Transfer admin role to another member or delete group
            $nextAdmin = $this->db->fetchColumn(
                "SELECT user_id FROM group_members WHERE group_id = :group_id AND role != 'admin' ORDER BY joined_at ASC LIMIT 1",
                ['group_id' => $groupId]
            );
            
            if ($nextAdmin) {
                $this->updateGroupMemberRole($groupId, $nextAdmin, 'admin');
            } else {
                // No other members, delete group
                return $this->deleteGroup($groupId);
            }
        }
        
        return $this->removeGroupMember($groupId, $userId);
    }
    
    private function addMessageAttachment(int $messageId, array $attachment): void {
        $this->db->insert('message_attachments', [
            'message_id' => $messageId,
            'filename' => $attachment['filename'],
            'original_name' => $attachment['original_name'],
            'file_size' => $attachment['file_size'],
            'mime_type' => $attachment['mime_type'],
            'file_path' => $attachment['file_path'],
            'created_at' => date('Y-m-d H:i:s')
        ]);
    }
    
    private function sendGroupMessageNotifications(int $groupId, int $messageId): void {
        $members = $this->getGroupMembers($groupId);
        $senderId = Auth::getUserId();
        
        foreach ($members as $member) {
            if ($member['id'] != $senderId) {
                // Create notification
                $this->db->insert('notifications', [
                    'user_id' => $member['id'],
                    'type' => 'group_message',
                    'title' => 'New group message',
                    'message' => 'You have a new message in a group',
                    'data' => json_encode([
                        'group_id' => $groupId,
                        'message_id' => $messageId,
                        'sender_id' => $senderId
                    ]),
                    'created_at' => date('Y-m-d H:i:s')
                ]);
                
                // Send push notification if enabled
                if ($member['push_notifications_enabled']) {
                    $this->sendPushNotification($member['id'], 'New group message', 'You have a new message in a group');
                }
            }
        }
    }
    
    private function sendGroupInviteNotification(int $groupId, int $userId): void {
        $group = $this->db->fetch(
            "SELECT name FROM message_groups WHERE id = :id",
            ['id' => $groupId]
        );
        
        $this->db->insert('notifications', [
            'user_id' => $userId,
            'type' => 'group_invite',
            'title' => 'Group invitation',
            'message' => "You have been invited to join the group '{$group['name']}'",
            'data' => json_encode(['group_id' => $groupId]),
            'created_at' => date('Y-m-d H:i:s')
        ]);
    }
    
    private function sendGroupLeaveNotification(int $groupId, int $userId): void {
        $group = $this->db->fetch(
            "SELECT name FROM message_groups WHERE id = :id",
            ['id' => $groupId]
        );
        
        $user = $this->db->fetch(
            "SELECT username FROM users WHERE id = :id",
            ['id' => $userId]
        );
        
        $this->db->insert('notifications', [
            'user_id' => $userId,
            'type' => 'group_leave',
            'title' => 'Left group',
            'message' => "You have left the group '{$group['name']}'",
            'data' => json_encode(['group_id' => $groupId]),
            'created_at' => date('Y-m-d H:i:s')
        ]);
    }
    
    private function sendPushNotification(int $userId, string $title, string $message): void {
        // This would integrate with a push notification service
        // For now, just log the notification
        error_log("Push notification for user {$userId}: {$title} - {$message}");
    }
    
    public function getGroupAnalytics(int $groupId): array {
        if (!$this->isGroupMember($groupId, Auth::getUserId())) {
            return [];
        }
        
        return [
            'total_messages' => $this->db->fetchColumn(
                "SELECT COUNT(*) FROM group_messages WHERE group_id = :group_id",
                ['group_id' => $groupId]
            ),
            'active_members' => $this->db->fetchColumn(
                "SELECT COUNT(DISTINCT user_id) FROM group_messages WHERE group_id = :group_id AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)",
                ['group_id' => $groupId]
            ),
            'messages_today' => $this->db->fetchColumn(
                "SELECT COUNT(*) FROM group_messages WHERE group_id = :group_id AND DATE(created_at) = CURDATE()",
                ['group_id' => $groupId]
            ),
            'most_active_member' => $this->db->fetch(
                "SELECT u.username, COUNT(*) as message_count
                 FROM group_messages gm
                 JOIN users u ON gm.user_id = u.id
                 WHERE gm.group_id = :group_id
                 GROUP BY gm.user_id, u.username
                 ORDER BY message_count DESC
                 LIMIT 1",
                ['group_id' => $groupId]
            )
        ];
    }
}