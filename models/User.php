<?php

//models/User.php

class User {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    public function findById($id) {
        return $this->db->fetch("SELECT * FROM users WHERE id = :id", ['id' => $id]);
    }
    
    public function findByUsername($username) {
        return $this->db->fetch("SELECT * FROM users WHERE username = :username", ['username' => $username]);
    }
    
    public function findByEmail($email) {
        return $this->db->fetch("SELECT * FROM users WHERE email = :email", ['email' => $email]);
    }
    
    public function findAll($limit = 20, $offset = 0) {
        return $this->db->fetchAll(
            "SELECT * FROM users ORDER BY created_at DESC LIMIT :limit OFFSET :offset",
            ['limit' => $limit, 'offset' => $offset]
        );
    }
    
    public function countAll() {
        $result = $this->db->fetch("SELECT COUNT(*) as count FROM users");
        return $result['count'];
    }
    
    public function create($data) {
        return $this->db->insert('users', $data);
    }
    
    public function update($id, $data) {
        return $this->db->update('users', $data, 'id = :id', ['id' => $id]);
    }
    
    public function delete($id) {
        return $this->db->delete('users', 'id = :id', ['id' => $id]);
    }
    
    public function updateLastLogin($id) {
        return $this->update($id, [
            'last_login' => date('Y-m-d H:i:s'),
            'login_ip' => $_SERVER['REMOTE_ADDR']
        ]);
    }
    
    public function incrementPostCount($id) {
        $user = $this->findById($id);
        $postCount = $user['posts_count'] + 1;
        
        return $this->update($id, ['posts_count' => $postCount]);
    }
    
    public function incrementThreadCount($id) {
        $user = $this->findById($id);
        $threadCount = $user['threads_count'] + 1;
        
        return $this->update($id, ['threads_count' => $threadCount]);
    }
    
    public function updateReputation($id, $points) {
        $user = $this->findById($id);
        $reputation = $user['reputation'] + $points;
        
        return $this->update($id, ['reputation' => $reputation]);
    }
    
    public function search($query, $limit = 20, $offset = 0) {
        return $this->db->fetchAll(
            "SELECT * FROM users 
             WHERE username LIKE :query OR email LIKE :query OR full_name LIKE :query 
             ORDER BY created_at DESC LIMIT :limit OFFSET :offset",
            ['query' => "%$query%", 'limit' => $limit, 'offset' => $offset]
        );
    }
    
    public function getTopUsers($limit = 10) {
        return $this->db->fetchAll(
            "SELECT * FROM users ORDER BY reputation DESC LIMIT :limit",
            ['limit' => $limit]
        );
    }
}
?>