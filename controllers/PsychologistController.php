<?php
// controllers/PsychologistController.php

class PsychologistController {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    public function index() {
        try {
            $user = AuthMiddleware::user();
            
            $stmt = $this->db->query("
                SELECT u.id, u.name, u.email, p.specialization, p.bio
                FROM psychologists p
                JOIN users u ON p.user_id = u.id
                ORDER BY u.name
            ");
            $psychologists = $stmt->fetchAll();
            
            Response::success($psychologists);
            
        } catch (Exception $e) {
            Response::error('Ошибка загрузки психологов: ' . $e->getMessage(), 500);
        }
    }
}