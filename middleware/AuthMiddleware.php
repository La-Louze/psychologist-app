<?php
// middleware/AuthMiddleware.php

class AuthMiddleware {
    private static $currentUser = null;
    
    public static function check() {
        // Получаем заголовки
        $headers = function_exists('getallheaders') ? getallheaders() : [];
        
        // Альтернативный способ получить заголовок
        $token = null;
        if (isset($headers['Authorization'])) {
            $token = str_replace('Bearer ', '', $headers['Authorization']);
        } elseif (isset($_SERVER['HTTP_AUTHORIZATION'])) {
            $token = str_replace('Bearer ', '', $_SERVER['HTTP_AUTHORIZATION']);
        }
        
        if (!$token) {
            Response::error('Требуется авторизация', 401);
        }
        
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM users WHERE api_token = ?");
        $stmt->execute([$token]);
        $user = $stmt->fetch();
        
        if (!$user) {
            Response::error('Недействительный токен', 401);
        }
        
        self::$currentUser = $user;
        return $user;
    }
    
    public static function user() {
        return self::$currentUser;
    }
    
    public static function isPsychologist() {
        return self::$currentUser && self::$currentUser['role'] === 'psychologist';
    }
    
    public static function isAdmin() {
        return self::$currentUser && self::$currentUser['role'] === 'admin';
    }
}