<?php
// controllers/AIChatController.php

class AIChatController {
    private $db;
    private $openAIService;
    
    public function __construct() {
        $this->db = Database::getInstance();
        $this->openAIService = new OpenAIService();
    }
    
    public function sendMessage() {
        try {
            $user = AuthMiddleware::user();
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (empty($input['message'])) {
                Response::error('Сообщение не может быть пустым', 400);
                return;
            }
            
            $stmt = $this->db->prepare("
                INSERT INTO ai_chat_messages (user_id, message, created_at)
                VALUES (?, ?, datetime('now'))
            ");
            $stmt->execute([$user['id'], $input['message']]);
            $messageId = $this->db->lastInsertId();
            
            $stmt = $this->db->prepare("
                SELECT message, response 
                FROM ai_chat_messages 
                WHERE user_id = ? 
                ORDER BY created_at DESC 
                LIMIT 10
            ");
            $stmt->execute([$user['id']]);
            $history = $stmt->fetchAll();
            $history = array_reverse($history);
            
            $response = $this->openAIService->chat('', $history, $input['message']);
            
            $stmt = $this->db->prepare("
                UPDATE ai_chat_messages SET response = ? WHERE id = ?
            ");
            $stmt->execute([$response, $messageId]);
            
            Response::success([
                'id' => $messageId,
                'message' => $input['message'],
                'response' => $response,
                'created_at' => date('Y-m-d H:i:s')
            ]);
            
        } catch (Exception $e) {
            Response::error('Ошибка при обработке сообщения: ' . $e->getMessage(), 500);
        }
    }
    
    public function history() {
        try {
            $user = AuthMiddleware::user();
            
            $stmt = $this->db->prepare("
                SELECT * FROM ai_chat_messages 
                WHERE user_id = ? 
                ORDER BY created_at DESC 
                LIMIT 50
            ");
            $stmt->execute([$user['id']]);
            $messages = $stmt->fetchAll();
            
            Response::success($messages);
            
        } catch (Exception $e) {
            Response::error('Ошибка загрузки истории: ' . $e->getMessage(), 500);
        }
    }
}