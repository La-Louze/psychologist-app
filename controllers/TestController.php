<?php
// controllers/TestController.php

class TestController {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    public function index() {
        try {
            $user = AuthMiddleware::user();
            
            $stmt = $this->db->query("SELECT * FROM tests ORDER BY title");
            $tests = $stmt->fetchAll();
            
            Response::success($tests);
            
        } catch (Exception $e) {
            Response::error('Ошибка загрузки тестов: ' . $e->getMessage(), 500);
        }
    }
    
    public function show($id) {
        try {
            $user = AuthMiddleware::user();
            
            $stmt = $this->db->prepare("SELECT * FROM tests WHERE id = ?");
            $stmt->execute([$id]);
            $test = $stmt->fetch();
            
            if (!$test) {
                Response::error('Тест не найден', 404);
                return;
            }
            
            $stmt = $this->db->prepare("SELECT * FROM test_questions WHERE test_id = ?");
            $stmt->execute([$id]);
            $questions = $stmt->fetchAll();
            
            $test['questions'] = $questions;
            
            Response::success($test);
            
        } catch (Exception $e) {
            Response::error('Ошибка загрузки теста: ' . $e->getMessage(), 500);
        }
    }
}