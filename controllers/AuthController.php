<?php
// controllers/AuthController.php

class AuthController {
    private $userModel;
    
    public function __construct() {
        $this->userModel = new User();
    }
    
    public function register() {
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            
            $errors = [];
            if (empty($input['name'])) $errors['name'] = 'Имя обязательно';
            if (empty($input['email'])) $errors['email'] = 'Email обязателен';
            if (empty($input['password'])) $errors['password'] = 'Пароль обязателен';
            if (strlen($input['password'] ?? '') < 6) $errors['password'] = 'Пароль должен быть не менее 6 символов';
            
            if ($this->userModel->findByEmail($input['email'])) {
                $errors['email'] = 'Email уже зарегистрирован';
            }
            
            if (!empty($errors)) {
                Response::validationError($errors);
                return;
            }
            
            $user = $this->userModel->create(
                $input['name'],
                $input['email'],
                $input['password'],
                $input['role'] ?? 'user'
            );
            
            if ($user) {
                Response::success([
                    'user' => $user,
                    'token' => $user['api_token']
                ], 'Регистрация успешна');
            } else {
                Response::error('Ошибка при регистрации', 500);
            }
            
        } catch (Exception $e) {
            Response::error('Ошибка: ' . $e->getMessage(), 500);
        }
    }
    
    public function login() {
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (empty($input['email']) || empty($input['password'])) {
                Response::error('Email и пароль обязательны', 400);
                return;
            }
            
            $user = $this->userModel->findByEmail($input['email']);
            
            if (!$user || !password_verify($input['password'], $user['password'])) {
                Response::error('Неверный email или пароль', 401);
                return;
            }
            
            $newToken = bin2hex(random_bytes(32));
            $this->userModel->updateToken($user['id'], $newToken);
            $user['api_token'] = $newToken;
            
            Response::success([
                'user' => $user,
                'token' => $newToken
            ], 'Вход выполнен успешно');
            
        } catch (Exception $e) {
            Response::error('Ошибка входа: ' . $e->getMessage(), 500);
        }
    }
    
    public function profile() {
        try {
            $user = AuthMiddleware::user();
            Response::success($user);
        } catch (Exception $e) {
            Response::error('Ошибка: ' . $e->getMessage(), 500);
        }
    }
    
    public function updateProfile() {
        try {
            $user = AuthMiddleware::user();
            $input = json_decode(file_get_contents('php://input'), true);
            
            $allowedFields = ['name', 'email'];
            $updateData = [];
            
            foreach ($allowedFields as $field) {
                if (isset($input[$field])) {
                    $updateData[$field] = $input[$field];
                }
            }
            
            if (isset($input['password']) && !empty($input['password'])) {
                $updateData['password'] = $input['password'];
            }
            
            if ($this->userModel->update($user['id'], $updateData)) {
                $updatedUser = $this->userModel->findById($user['id']);
                Response::success($updatedUser, 'Профиль обновлен');
            } else {
                Response::error('Ошибка при обновлении', 500);
            }
            
        } catch (Exception $e) {
            Response::error('Ошибка: ' . $e->getMessage(), 500);
        }
    }
    
    public function logout() {
        try {
            $user = AuthMiddleware::user();
            $this->userModel->updateToken($user['id'], null);
            Response::success(null, 'Выход выполнен успешно');
        } catch (Exception $e) {
            Response::error('Ошибка: ' . $e->getMessage(), 500);
        }
    }
}