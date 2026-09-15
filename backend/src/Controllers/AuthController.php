<?php

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\Csrf;
use App\Core\Validator;
use App\Models\User;
use App\Models\AuditLog;
use App\Models\PasswordReset;

class AuthController {
    public function csrfToken(Request $request): void {
        $token = Csrf::getToken();
        Response::success(['csrf_token' => $token]);
    }

    public function register(Request $request): void {
        $username = trim((string)$request->get('username', ''));
        $email = trim((string)$request->get('email', ''));
        $password = (string)$request->get('password', '');

        if (empty($username) || empty($email) || empty($password)) {
            Response::error('Todos los campos son obligatorios: username, email y password', 400);
        }

        if (!Validator::validateUsername($username)) {
            Response::error('El nombre de usuario debe tener entre 3 y 50 caracteres alfanuméricos', 400);
        }

        if (!Validator::validateEmail($email)) {
            Response::error('El formato del correo electrónico es inválido', 400);
        }

        if (!Validator::validatePassword($password)) {
            Response::error('La contraseña debe tener al menos 8 caracteres, incluir una letra mayúscula, un número y un símbolo', 400);
        }

        if (User::findByUsername($username)) {
            Response::error('El nombre de usuario ya se encuentra registrado', 409);
        }

        if (User::findByEmail($email)) {
            Response::error('El correo electrónico ya se encuentra registrado', 409);
        }

        $userId = User::create($username, $email, $password, 2);

        AuditLog::log(
            $userId,
            $username,
            'REGISTER',
            $request->getIpAddress(),
            $request->getUserAgent(),
            'success',
            'Usuario registrado exitosamente'
        );

        Response::success([
            'id' => $userId,
            'username' => $username,
            'email' => $email,
            'role' => 'usuario'
        ], 'Usuario registrado exitosamente', 201);
    }

    public function login(Request $request): void {
        $identifier = trim((string)$request->get('username', ''));
        if (empty($identifier)) {
            $identifier = trim((string)$request->get('email', ''));
        }
        $password = (string)$request->get('password', '');

        if (empty($identifier) || empty($password)) {
            Response::error('Debe ingresar usuario o correo y contraseña', 400);
        }

        $user = User::findByUsernameOrEmail($identifier);

        if (!$user) {
            AuditLog::log(
                null,
                $identifier,
                'LOGIN_FAILED',
                $request->getIpAddress(),
                $request->getUserAgent(),
                'failure',
                'Usuario o email no encontrado'
            );
            Response::error('Credenciales inválidas', 401);
        }

        if (User::isLocked($user)) {
            AuditLog::log(
                $user['id'],
                $user['username'],
                'LOGIN_BLOCKED',
                $request->getIpAddress(),
                $request->getUserAgent(),
                'failure',
                'Intento de acceso a cuenta bloqueada por fuerza bruta'
            );
            Response::error('La cuenta se encuentra temporalmente bloqueada por reiterados intentos fallidos. Intente nuevamente en unos minutos', 429);
        }

        if ((int)$user['is_active'] === 0) {
            AuditLog::log(
                $user['id'],
                $user['username'],
                'LOGIN_INACTIVE',
                $request->getIpAddress(),
                $request->getUserAgent(),
                'failure',
                'Intento de acceso a cuenta inactiva o deshabilitada'
            );
            Response::error('La cuenta se encuentra deshabilitada. Contacte al administrador', 403);
        }

        if (!password_verify($password, $user['password_hash'])) {
            $attempts = User::incrementFailedAttempts($user['id']);
            AuditLog::log(
                $user['id'],
                $user['username'],
                'LOGIN_FAILED',
                $request->getIpAddress(),
                $request->getUserAgent(),
                'failure',
                "Contraseña incorrecta. Intento {$attempts}"
            );
            Response::error('Credenciales inválidas', 401);
        }

        User::resetFailedAttempts($user['id']);

        Session::start();
        Session::regenerate();
        Session::set('user_id', (int)$user['id']);
        Session::set('username', $user['username']);
        Session::set('role', $user['role']);
        Session::set('email', $user['email']);

        $csrfToken = Csrf::getToken();

        AuditLog::log(
            $user['id'],
            $user['username'],
            'LOGIN_SUCCESS',
            $request->getIpAddress(),
            $request->getUserAgent(),
            'success',
            'Inicio de sesión exitoso'
        );

        Response::success([
            'id' => (int)$user['id'],
            'username' => $user['username'],
            'email' => $user['email'],
            'role' => $user['role'],
            'csrf_token' => $csrfToken
        ], 'Inicio de sesión exitoso');
    }

    public function logout(Request $request): void {
        Session::start();
        $userId = Session::get('user_id');
        $username = Session::get('username');

        if ($userId) {
            AuditLog::log(
                $userId,
                $username,
                'LOGOUT',
                $request->getIpAddress(),
                $request->getUserAgent(),
                'success',
                'Cierre de sesión'
            );
        }

        Session::destroy();
        Response::success(null, 'Sesión finalizada correctamente');
    }

    public function me(Request $request): void {
        Session::start();
        $userId = Session::get('user_id');
        if (!$userId) {
            Response::error('No autenticado', 401);
        }

        $user = User::findById($userId);
        if (!$user) {
            Session::destroy();
            Response::error('Usuario no encontrado', 404);
        }

        Response::success([
            'id' => (int)$user['id'],
            'username' => $user['username'],
            'email' => $user['email'],
            'role' => $user['role'],
            'is_active' => (bool)$user['is_active'],
            'csrf_token' => Csrf::getToken()
        ]);
    }

    public function forgotPassword(Request $request): void {
        $email = trim((string)$request->get('email', ''));

        if (empty($email) || !Validator::validateEmail($email)) {
            Response::error('Debe proporcionar un correo electrónico válido', 400);
        }

        $user = User::findByEmail($email);
        if ($user) {
            $resetToken = PasswordReset::createToken($user['id']);
            error_log("PASSWORD_RESET_TOKEN [{$email}]: {$resetToken}");

            AuditLog::log(
                $user['id'],
                $user['username'],
                'FORGOT_PASSWORD_REQUEST',
                $request->getIpAddress(),
                $request->getUserAgent(),
                'success',
                'Solicitud de restablecimiento de contraseña procesada'
            );
        }

        Response::success(null, 'Si el correo electrónico se encuentra registrado, se enviaron las instrucciones para restablecer la contraseña');
    }

    public function resetPassword(Request $request): void {
        $token = trim((string)$request->get('token', ''));
        $newPassword = (string)$request->get('password', '');

        if (empty($token) || empty($newPassword)) {
            Response::error('Token y nueva contraseña requeridos', 400);
        }

        if (!Validator::validatePassword($newPassword)) {
            Response::error('La nueva contraseña debe tener al menos 8 caracteres, una mayúscula, un número y un símbolo', 400);
        }

        $reset = PasswordReset::verifyToken($token);
        if (!$reset) {
            Response::error('El token de restablecimiento es inválido o ha expirado', 400);
        }

        $userId = (int)$reset['user_id'];
        User::updatePassword($userId, $newPassword);
        PasswordReset::markAsUsed($reset['id']);

        $user = User::findById($userId);
        AuditLog::log(
            $userId,
            $user ? $user['username'] : null,
            'PASSWORD_RESET_SUCCESS',
            $request->getIpAddress(),
            $request->getUserAgent(),
            'success',
            'Contraseña restablecida con token'
        );

        Response::success(null, 'Contraseña actualizada correctamente. Ya puede iniciar sesión');
    }
}
