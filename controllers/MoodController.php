<?php
// controllers/MoodController.php

class MoodController {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    public function index() {
        try {
            $user = AuthMiddleware::user();
            
            $stmt = $this->db->prepare("
                SELECT * FROM mood_entries 
                WHERE user_id = ? 
                ORDER BY date DESC 
                LIMIT 30
            ");
            $stmt->execute([$user['id']]);
            $entries = $stmt->fetchAll();
            
            Response::success($entries);
            
        } catch (Exception $e) {
            Response::error('Ошибка загрузки дневника: ' . $e->getMessage(), 500);
        }
    }
    
    public function store() {
        try {
            $user = AuthMiddleware::user();
            $input = json_decode(file_get_contents('php://input'), true);
            
            $errors = [];
            if (empty($input['mood_level']) || $input['mood_level'] < 1 || $input['mood_level'] > 10) {
                $errors['mood_level'] = 'Уровень настроения должен быть от 1 до 10';
            }
            if (empty($input['date'])) {
                $errors['date'] = 'Дата обязательна';
            }
            
            if (!empty($errors)) {
                Response::validationError($errors);
                return;
            }
            
            $stmt = $this->db->prepare("
                SELECT * FROM mood_entries 
                WHERE user_id = ? AND date = ?
            ");
            $stmt->execute([$user['id'], $input['date']]);
            $existing = $stmt->fetch();
            
            if ($existing) {
                $stmt = $this->db->prepare("
                    UPDATE mood_entries 
                    SET mood_level = ?, note = ? 
                    WHERE id = ?
                ");
                $stmt->execute([
                    $input['mood_level'],
                    $input['note'] ?? null,
                    $existing['id']
                ]);
            } else {
                $stmt = $this->db->prepare("
                    INSERT INTO mood_entries (user_id, mood_level, note, date, created_at)
                    VALUES (?, ?, ?, ?, datetime('now'))
                ");
                $stmt->execute([
                    $user['id'],
                    $input['mood_level'],
                    $input['note'] ?? null,
                    $input['date']
                ]);
            }
            
            $stmt = $this->db->prepare("
                SELECT * FROM mood_entries 
                WHERE user_id = ? AND date = ?
            ");
            $stmt->execute([$user['id'], $input['date']]);
            $entry = $stmt->fetch();
            
            $recommendation = null;
            if ($input['mood_level'] <= 3) {
                $recommendation = 'Заметили, что ваше настроение снизилось. Возможно, вам помогут упражнения на расслабление или разговор с психологом?';
            }
            
            Response::success([
                'entry' => $entry,
                'recommendation' => $recommendation
            ], 'Запись сохранена');
            
        } catch (Exception $e) {
            Response::error('Ошибка сохранения: ' . $e->getMessage(), 500);
        }
    }
}