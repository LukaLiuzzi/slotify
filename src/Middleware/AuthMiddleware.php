<?php

namespace App\Middleware;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\Csrf;

class AuthMiddleware {
    public function handle(Request $request, array $params = []): void {
        Session::start();

        $userId = Session::get('user_id');
        if (!$userId) {
            Response::error('No autenticado. Inicie sesión para continuar', 401);
        }

        $method = $request->getMethod();
        if (in_array($method, ['POST', 'PUT', 'DELETE'], true)) {
            $csrfHeader = $request->getHeader('X-CSRF-Token');
            $csrfBody = $request->get('csrf_token');
            $token = $csrfHeader ?: $csrfBody;

            if (!Csrf::validateToken($token)) {
                Response::error('Token CSRF inválido o faltante', 403);
            }
        }
    }
}
