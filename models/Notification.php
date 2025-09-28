<?php

//models/Notification.php

class Notification {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    public function findById($id) {
        return $this->db->fetch("SELECT * FROM notifications WHERE id = :id", ['id' => $id]);
    }
    
    public function findByUser($userId, $limit = 20, $offset = 0) {
        return $this->db->fetchAll(
            "SELECT * FROM notifications 
             WHERE user_id = :user_id 
             ORDER BY created_at DESC 
             LIMIT :limit OFFSET :offset",
            ['user_id' => $userId, 'limit' => $limit, 'offset' => $offset]
        );
    }
    
    public function create($data) {
        return $this->db->insert('notifications', $data);
    }
    
    public function markAsRead($id) {
        return $this->db->update('notifications', ['is_read' => 1], 'id = :id', ['id' => $id]);
    }
    
    public function markAllAsRead($userId) {
        return $this->db->update(
            'notifications', 
            ['is_read' => 1], 
            'user_id = :user_id AND is_read = 0',
            ['user_id' => $userId]
        );
    }
    
    public function getUnreadCount($userId) {
        $result = $this->db->fetch(
            "SELECT COUNT(*) as count FROM notifications WHERE user_id = :user_id AND is_read = 0",
            ['user_id' => $userId]
        );
        return $result['count'];
    }
    
    public function delete($id) {
        return $this->db->delete('notifications', 'id = :id', ['id' => $id]);
    }
    
    public function deleteAllByUser($userId) {
        return $this->db->delete('notifications', 'user_id = :user_id', ['user_id' => $userId]);
    }
}
?>