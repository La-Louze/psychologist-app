<?php
// controllers/ExerciseController.php

class ExerciseController {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    public function index() {
        try {
            $user = AuthMiddleware::user();
            
            $stmt = $this->db->query("SELECT * FROM exercises ORDER BY category, title");
            $exercises = $stmt->fetchAll();
            
            $stmt2 = $this->db->prepare("
                SELECT exercise_id FROM user_exercise_progress 
                WHERE user_id = ? AND completed_at IS NOT NULL
            ");
            $stmt2->execute([$user['id']]);
            $completedIds = $stmt2->fetchAll(PDO::FETCH_COLUMN);
            
            foreach ($exercises as &$exercise) {
                $exercise['completed'] = in_array($exercise['id'], $completedIds);
            }
            
            Response::success($exercises);
            
        } catch (Exception $e) {
            Response::error('Ошибка загрузки упражнений: ' . $e->getMessage(), 500);
        }
    }
    
    public function complete($exerciseId) {
        try {
            $user = AuthMiddleware::user();
            
            $stmt = $this->db->prepare("SELECT * FROM exercises WHERE id = ?");
            $stmt->execute([$exerciseId]);
            $exercise = $stmt->fetch();
            
            if (!$exercise) {
                Response::error('Упражнение не найдено', 404);
                return;
            }
            
            $stmt = $this->db->prepare("
                SELECT * FROM user_exercise_progress 
                WHERE user_id = ? AND exercise_id = ? AND completed_at IS NOT NULL
            ");
            $stmt->execute([$user['id'], $exerciseId]);
            
            if ($stmt->fetch()) {
                Response::error('Упражнение уже выполнено', 409);
                return;
            }
            
            $stmt = $this->db->prepare("
                INSERT INTO user_exercise_progress (user_id, exercise_id, completed_at)
                VALUES (?, ?, datetime('now'))
                ON CONFLICT(user_id, exercise_id) DO UPDATE SET completed_at = datetime('now')
            ");
            
            if ($stmt->execute([$user['id'], $exerciseId])) {
                Response::success([
                    'exercise_id' => $exerciseId,
                    'title' => $exercise['title']
                ], 'Упражнение отмечено как выполненное');
            } else {
                Response::error('Ошибка при сохранении', 500);
            }
            
        } catch (Exception $e) {
            Response::error('Ошибка: ' . $e->getMessage(), 500);
        }
    }
}