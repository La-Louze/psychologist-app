<?php
// controllers/ChecklistController.php

class ChecklistController {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    public function index() {
        try {
            $user = AuthMiddleware::user();
            
            $stmt = $this->db->query("
                SELECT c.*, 
                    (SELECT COUNT(*) FROM checklist_items WHERE checklist_id = c.id) as items_count
                FROM checklists c
                ORDER BY c.title
            ");
            $checklists = $stmt->fetchAll();
            
            Response::success($checklists);
            
        } catch (Exception $e) {
            Response::error('Ошибка загрузки чек-листов: ' . $e->getMessage(), 500);
        }
    }
}