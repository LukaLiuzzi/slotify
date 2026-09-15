<?php

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Models\User;
use App\Models\AuditLog;

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
}
