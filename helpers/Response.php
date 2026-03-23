<?php
// helpers/Response.php

class Response {
    public static function json($data, $statusCode = 200) {
        // Очищаем буфер вывода
        if (ob_get_level()) {
            ob_clean();
        }
        
        // Устанавливаем правильные заголовки
        header('Content-Type: application/json; charset=utf-8');
        header('Access-Control-Allow-Origin: *');
        http_response_code($statusCode);
        
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit();
    }
    
    public static function success($data = null, $message = 'Успешно') {
        self::json([
            'success' => true,
            'message' => $message,
            'data' => $data
        ], 200);
    }
    
    public static function error($message, $statusCode = 400) {
        self::json([
            'success' => false,
            'error' => $message
        ], $statusCode);
    }
    
    public static function validationError($errors) {
        self::json([
            'success' => false,
            'errors' => $errors
        ], 422);
    }
}