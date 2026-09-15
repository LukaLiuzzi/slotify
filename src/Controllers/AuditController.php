<?php

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Models\AuditLog;

class AuditController {
    public function index(Request $request): void {
        $limit = (int)$request->get('limit', 50);
        $offset = (int)$request->get('offset', 0);
        $username = $request->get('username');
        $action = $request->get('action');
        $status = $request->get('status');

        if ($limit < 1) {
            $limit = 50;
        }
        if ($limit > 200) {
            $limit = 200;
        }

        $logs = AuditLog::getAll($limit, $offset, $username, $action, $status);
        Response::success([
            'limit' => $limit,
            'offset' => $offset,
            'logs' => $logs
        ]);
    }
}
