<?php
// controllers/ArtController.php

class ArtController {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    public function index() {
        try {
            $user = AuthMiddleware::user();
            
            $stmt = $this->db->prepare("
                SELECT * FROM art_therapy 
                WHERE user_id = ? 
                ORDER BY created_at DESC
            ");
            $stmt->execute([$user['id']]);
            $artworks = $stmt->fetchAll();
            
            Response::success($artworks);
            
        } catch (Exception $e) {
            Response::error('Ошибка загрузки: ' . $e->getMessage(), 500);
        }
    }
    
    public function store() {
        try {
            $user = AuthMiddleware::user();
            $input = json_decode(file_get_contents('php://input'), true);
            
            $stmt = $this->db->prepare("
                INSERT INTO art_therapy (user_id, description, created_at)
                VALUES (?, ?, datetime('now'))
            ");
            
            if ($stmt->execute([$user['id'], $input['description'] ?? null])) {
                $id = $this->db->lastInsertId();
                
                $stmt = $this->db->prepare("SELECT * FROM art_therapy WHERE id = ?");
                $stmt->execute([$id]);
                $artwork = $stmt->fetch();
                
                Response::success($artwork, 'Работа сохранена');
            } else {
                Response::error('Ошибка сохранения', 500);
            }
            
        } catch (Exception $e) {
            Response::error('Ошибка: ' . $e->getMessage(), 500);
        }
    }
}