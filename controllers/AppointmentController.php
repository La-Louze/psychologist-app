<?php
// controllers/AppointmentController.php

class AppointmentController {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    public function index() {
        try {
            $user = AuthMiddleware::user();
            
            if ($user['role'] === 'psychologist') {
                $stmt = $this->db->prepare("
                    SELECT a.*, u.name as user_name, u.email as user_email
                    FROM appointments a
                    JOIN users u ON a.user_id = u.id
                    WHERE a.psychologist_id = ?
                    ORDER BY a.appointment_time DESC
                ");
                $psychologist = $this->db->prepare("SELECT id FROM psychologists WHERE user_id = ?");
                $psychologist->execute([$user['id']]);
                $psych = $psychologist->fetch();
                $stmt->execute([$psych['id']]);
            } else {
                $stmt = $this->db->prepare("
                    SELECT a.*, p.user_id as psychologist_user_id, u.name as psychologist_name
                    FROM appointments a
                    JOIN psychologists p ON a.psychologist_id = p.id
                    JOIN users u ON p.user_id = u.id
                    WHERE a.user_id = ?
                    ORDER BY a.appointment_time DESC
                ");
                $stmt->execute([$user['id']]);
            }
            
            $appointments = $stmt->fetchAll();
            Response::success($appointments);
            
        } catch (Exception $e) {
            Response::error('Ошибка загрузки записей: ' . $e->getMessage(), 500);
        }
    }
    
    public function store() {
        try {
            $user = AuthMiddleware::user();
            $input = json_decode(file_get_contents('php://input'), true);
            
            $errors = [];
            if (empty($input['psychologist_id'])) $errors['psychologist_id'] = 'Выберите психолога';
            if (empty($input['appointment_time'])) $errors['appointment_time'] = 'Выберите время';
            
            if (!empty($errors)) {
                Response::validationError($errors);
                return;
            }
            
            $stmt = $this->db->prepare("SELECT * FROM psychologists WHERE id = ?");
            $stmt->execute([$input['psychologist_id']]);
            $psychologist = $stmt->fetch();
            
            if (!$psychologist) {
                Response::error('Психолог не найден', 404);
                return;
            }
            
            $stmt = $this->db->prepare("
                SELECT * FROM appointments 
                WHERE psychologist_id = ? AND appointment_time = ? AND status != 'cancelled'
            ");
            $stmt->execute([$input['psychologist_id'], $input['appointment_time']]);
            
            if ($stmt->fetch()) {
                Response::error('Это время уже занято', 409);
                return;
            }
            
            $stmt = $this->db->prepare("
                INSERT INTO appointments (user_id, psychologist_id, appointment_time, status, created_at)
                VALUES (?, ?, ?, 'pending', datetime('now'))
            ");
            
            if ($stmt->execute([$user['id'], $input['psychologist_id'], $input['appointment_time']])) {
                $appointmentId = $this->db->lastInsertId();
                
                $stmt = $this->db->prepare("
                    SELECT a.*, u.name as psychologist_name
                    FROM appointments a
                    JOIN psychologists p ON a.psychologist_id = p.id
                    JOIN users u ON p.user_id = u.id
                    WHERE a.id = ?
                ");
                $stmt->execute([$appointmentId]);
                $appointment = $stmt->fetch();
                
                Response::success($appointment, 'Запись создана');
            } else {
                Response::error('Ошибка при создании записи', 500);
            }
            
        } catch (Exception $e) {
            Response::error('Ошибка: ' . $e->getMessage(), 500);
        }
    }
    
    public function cancel($id) {
        try {
            $user = AuthMiddleware::user();
            
            $stmt = $this->db->prepare("
                SELECT a.*, p.user_id as psychologist_user_id
                FROM appointments a
                JOIN psychologists p ON a.psychologist_id = p.id
                WHERE a.id = ?
            ");
            $stmt->execute([$id]);
            $appointment = $stmt->fetch();
            
            if (!$appointment) {
                Response::error('Запись не найдена', 404);
                return;
            }
            
            if ($appointment['user_id'] != $user['id'] && $appointment['psychologist_user_id'] != $user['id']) {
                Response::error('Доступ запрещен', 403);
                return;
            }
            
            $stmt = $this->db->prepare("
                UPDATE appointments SET status = 'cancelled' WHERE id = ?
            ");
            $stmt->execute([$id]);
            
            Response::success(null, 'Запись отменена');
            
        } catch (Exception $e) {
            Response::error('Ошибка: ' . $e->getMessage(), 500);
        }
    }
}