<?php
// models/User.php

class User {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    public function create($name, $email, $password, $role = 'user') {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $apiToken = bin2hex(random_bytes(32));
        
        $stmt = $this->db->prepare("
            INSERT INTO users (name, email, password, role, api_token, created_at)
            VALUES (?, ?, ?, ?, ?, NOW())
        ");
        
        if ($stmt->execute([$name, $email, $hashedPassword, $role, $apiToken])) {
            $userId = $this->db->lastInsertId();
            
            // Если пользователь - психолог, создаем запись в psychologists
            if ($role === 'psychologist') {
                $stmt2 = $this->db->prepare("
                    INSERT INTO psychologists (user_id) VALUES (?)
                ");
                $stmt2->execute([$userId]);
            }
            
            return $this->findById($userId);
        }
        
        return false;
    }
    
    public function findByEmail($email) {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        return $stmt->fetch();
    }
    
    public function findById($id) {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$id]);
        $user = $stmt->fetch();
        
        if ($user && $user['role'] === 'psychologist') {
            $stmt2 = $this->db->prepare("SELECT * FROM psychologists WHERE user_id = ?");
            $stmt2->execute([$id]);
            $user['psychologist'] = $stmt2->fetch();
        }
        
        return $user;
    }
    
    public function update($id, $data) {
        $fields = [];
        $values = [];
        
        if (isset($data['name'])) {
            $fields[] = "name = ?";
            $values[] = $data['name'];
        }
        if (isset($data['email'])) {
            $fields[] = "email = ?";
            $values[] = $data['email'];
        }
        if (isset($data['password'])) {
            $fields[] = "password = ?";
            $values[] = password_hash($data['password'], PASSWORD_DEFAULT);
        }
        
        if (empty($fields)) {
            return false;
        }
        
        $values[] = $id;
        $stmt = $this->db->prepare("UPDATE users SET " . implode(', ', $fields) . " WHERE id = ?");
        return $stmt->execute($values);
    }
    
    public function updateToken($userId, $token) {
        $stmt = $this->db->prepare("UPDATE users SET api_token = ? WHERE id = ?");
        return $stmt->execute([$token, $userId]);
    }
}