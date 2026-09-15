<?php

namespace App\Middleware;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;

class AdminMiddleware {
    public function handle(Request $request, array $params = []): void {
        (new AuthMiddleware())->handle($request, $params);

        $role = Session::get('role');
        if ($role !== 'admin') {
            Response::error('Acceso denegado. Se requieren permisos de administrador', 403);
        }
    }
}
