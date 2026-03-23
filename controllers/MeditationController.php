<?php
// controllers/MeditationController.php

class MeditationController {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    public function index() {
        try {
            $user = AuthMiddleware::user();
            
            $stmt = $this->db->query("SELECT * FROM audio_content ORDER BY type, title");
            $audio = $stmt->fetchAll();
            
            Response::success($audio);
            
        } catch (Exception $e) {
            Response::error('Ошибка загрузки медитаций: ' . $e->getMessage(), 500);
        }
    }
}