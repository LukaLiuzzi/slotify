<?php

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Models\User;
use App\Models\AuditLog;
use App\Models\PasswordChangeRequest;
use App\Core\Validator;

class UserController {
    public function index(Request $request): void {
        $email = $request->get('email');
        $username = $request->get('username');

        $users = User::getAll($email, $username);
        Response::success($users);
    }

    public function toggleStatus(Request $request, array $params): void {
        $id = (int)($params['id'] ?? 0);
        $user = User::findById($id);

        if (!$user) {
            Response::error('Usuario no encontrado', 404);
        }

        $isActive = $request->get('is_active');
        $unlock = $request->get('unlock');

        if ($isActive !== null) {
            User::updateStatus($id, (bool)$isActive);
        }

        if ($unlock === true || $unlock === 'true' || $unlock === 1 || $unlock === '1') {
            User::unlockAccount($id);
        }

        $adminId = Session::get('user_id');
        $adminUsername = Session::get('username');

        AuditLog::log(
            $adminId,
            $adminUsername,
            'ADMIN_UPDATE_USER_STATUS',
            $request->getIpAddress(),
            $request->getUserAgent(),
            'success',
            "Estado de usuario ID {$id} actualizado"
        );

        $updatedUser = User::findById($id);
        Response::success($updatedUser, 'Estado del usuario actualizado correctamente');
    }

    public function delete(Request $request, array $params): void {
        $id = (int)($params['id'] ?? 0);
        $user = User::findById($id);

        if (!$user) {
            Response::error('Usuario no encontrado', 404);
        }

        $currentUserId = Session::get('user_id');
        if ($id === $currentUserId) {
            Response::error('No puede eliminarse a sí mismo como administrador', 400);
        }

        User::delete($id);

        $adminUsername = Session::get('username');
        AuditLog::log(
            $currentUserId,
            $adminUsername,
            'ADMIN_DELETE_USER',
            $request->getIpAddress(),
            $request->getUserAgent(),
            'success',
            "Usuario {$user['username']} (ID {$id}) eliminado"
        );

        Response::success(null, 'Usuario eliminado correctamente');
    }

    public function passwordRequests(Request $request): void {
        Response::success(PasswordChangeRequest::getPending());
    }

    public function changePassword(Request $request, array $params): void {
        $requestId = (int)($params['id'] ?? 0);
        $solicitud = PasswordChangeRequest::findPending($requestId);

        if (!$solicitud) {
            Response::error('La solicitud no existe o ya fue atendida', 404);
        }

        $user = User::findById((int)$solicitud['user_id']);
        if (!$user) {
            Response::error('Usuario no encontrado', 404);
        }

        $newPassword = (string)$request->get('password', '');

        if (!Validator::validatePassword($newPassword, $user['username'], $user['email'])) {
            Response::error('La contraseña debe tener entre 8 y 48 caracteres, incluir una letra mayúscula, un número y un símbolo, y no puede contener el nombre de usuario o correo del usuario', 400);
        }

        User::updatePassword((int)$user['id'], $newPassword);
        User::resetFailedAttempts((int)$user['id']);
        User::unlockAccount((int)$user['id']);
        PasswordChangeRequest::resolve($requestId, Session::get('user_id'));

        AuditLog::log(
            Session::get('user_id'),
            Session::get('username'),
            'ADMIN_CHANGE_PASSWORD',
            $request->getIpAddress(),
            $request->getUserAgent(),
            'success',
            "Contraseña cambiada al usuario {$user['username']} (solicitud {$requestId})"
        );

        Response::success(null, "Contraseña del usuario {$user['username']} actualizada correctamente");
    }

    public function rejectPasswordRequest(Request $request, array $params): void {
        $requestId = (int)($params['id'] ?? 0);
        $solicitud = PasswordChangeRequest::findPending($requestId);

        if (!$solicitud) {
            Response::error('La solicitud no existe o ya fue atendida', 404);
        }

        $user = User::findById((int)$solicitud['user_id']);

        PasswordChangeRequest::reject($requestId, Session::get('user_id'));

        AuditLog::log(
            Session::get('user_id'),
            Session::get('username'),
            'ADMIN_CHANGE_PASSWORD',
            $request->getIpAddress(),
            $request->getUserAgent(),
            'success',
            "Solicitud de cambio de contraseña rechazada" . ($user ? " (usuario {$user['username']})" : "")
        );

        Response::success(null, 'Solicitud rechazada correctamente');
    }
}
