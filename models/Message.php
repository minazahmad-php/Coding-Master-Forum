<?php

//models/Message.php

class Message {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    public function findById($id) {
        return $this->db->fetch(
            "SELECT m.*, s.username as sender_name, r.username as receiver_name
             FROM messages m 
             LEFT JOIN users s ON m.sender_id = s.id 
             LEFT JOIN users r ON m.receiver_id = r.id 
             WHERE m.id = :id",
            ['id' => $id]
        );
    }
    
    public function findByUsers($userId1, $userId2, $limit = 50, $offset = 0) {
        return $this->db->fetchAll(
            "SELECT m.*, s.username as sender_name, s.avatar as sender_avatar,
             r.username as receiver_name, r.avatar as receiver_avatar
             FROM messages m 
             LEFT JOIN users s ON m.sender_id = s.id 
             LEFT JOIN users r ON m.receiver_id = r.id 
             WHERE (m.sender_id = :user_id1 AND m.receiver_id = :user_id2) 
                OR (m.sender_id = :user_id2 AND m.receiver_id = :user_id1) 
             ORDER BY m.created_at ASC 
             LIMIT :limit OFFSET :offset",
            ['user_id1' => $userId1, 'user_id2' => $userId2, 'limit' => $limit, 'offset' => $offset]
        );
    }
    
    public function findBySender($senderId, $limit = 20, $offset = 0) {
        return $this->db->fetchAll(
            "SELECT m.*, r.username as receiver_name, r.avatar as receiver_avatar
             FROM messages m 
             LEFT JOIN users r ON m.receiver_id = r.id 
             WHERE m.sender_id = :sender_id 
             ORDER BY m.created_at DESC 
             LIMIT :limit OFFSET :offset",
            ['sender_id' => $senderId, 'limit' => $limit, 'offset' => $offset]
        );
    }
    
    public function findByReceiver($receiverId, $limit = 20, $offset = 0) {
        return $this->db->fetchAll(
            "SELECT m.*, s.username as sender_name, s.avatar as sender_avatar
             FROM messages m 
             LEFT JOIN users s ON m.sender_id = s.id 
             WHERE m.receiver_id = :receiver_id 
             ORDER BY m.created_at DESC 
             LIMIT :limit OFFSET :offset",
            ['receiver_id' => $receiverId, 'limit' => $limit, 'offset' => $offset]
        );
    }
    
    public function getConversations($userId, $limit = 20, $offset = 0) {
        // Get the latest message from each conversation
        return $this->db->fetchAll(
            "SELECT m.*, 
                CASE 
                    WHEN m.sender_id = :user_id THEN m.receiver_id 
                    ELSE m.sender_id 
                END as other_user_id,
                CASE 
                    WHEN m.sender_id = :user_id THEN r.username 
                    ELSE s.username 
                END as other_username,
                CASE 
                    WHEN m.sender_id = :user_id THEN r.avatar 
                    ELSE s.avatar 
                END as other_avatar,
                (SELECT COUNT(*) FROM messages WHERE 
                    ((sender_id = :user_id AND receiver_id = other_user_id) 
                    OR (sender_id = other_user_id AND receiver_id = :user_id)) 
                    AND is_read = 0 AND receiver_id = :user_id) as unread_count
             FROM messages m 
             LEFT JOIN users s ON m.sender_id = s.id 
             LEFT JOIN users r ON m.receiver_id = r.id 
             WHERE m.id IN (
                 SELECT MAX(id) 
                 FROM messages 
                 WHERE sender_id = :user_id OR receiver_id = :user_id 
                 GROUP BY LEAST(sender_id, receiver_id), GREATEST(sender_id, receiver_id)
             )
             ORDER BY m.created_at DESC 
             LIMIT :limit OFFSET :offset",
            ['user_id' => $userId, 'limit' => $limit, 'offset' => $offset]
        );
    }
    
    public function create($data) {
        return $this->db->insert('messages', $data);
    }
    
    public function markAsRead($id) {
        return $this->db->update('messages', ['is_read' => 1], 'id = :id', ['id' => $id]);
    }
    
    public function markConversationAsRead($userId, $otherUserId) {
        return $this->db->update(
            'messages', 
            ['is_read' => 1], 
            'receiver_id = :user_id AND sender_id = :other_user_id AND is_read = 0',
            ['user_id' => $userId, 'other_user_id' => $otherUserId]
        );
    }
    
    public function getUnreadCount($userId) {
        $result = $this->db->fetch(
            "SELECT COUNT(*) as count FROM messages WHERE receiver_id = :user_id AND is_read = 0",
            ['user_id' => $userId]
        );
        return $result['count'];
    }
    
    public function delete($id) {
        return $this->db->delete('messages', 'id = :id', ['id' => $id]);
    }
}
?>