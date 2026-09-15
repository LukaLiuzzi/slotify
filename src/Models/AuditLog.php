<?php

namespace App\Models;

use App\Config\Database;
use PDO;

class AuditLog {
    public static function log(
        ?int $userId,
        ?string $username,
        string $action,
        string $ipAddress,
        ?string $userAgent,
        string $status,
        ?string $details = null
    ): void {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            INSERT INTO audit_logs (user_id, username, action, ip_address, user_agent, status, details)
            VALUES (:user_id, :username, :action, :ip_address, :user_agent, :status, :details)
        ");
        $stmt->execute([
            ':user_id' => $userId,
            ':username' => $username,
            ':action' => $action,
            ':ip_address' => $ipAddress,
            ':user_agent' => substr($userAgent ?? '', 0, 255),
            ':status' => $status,
            ':details' => $details
        ]);
    }

    public static function getAll(
        int $limit = 50, 
        int $offset = 0, 
        ?string $username = null, 
        ?string $action = null,
        ?string $status = null
    ): array {
        $db = Database::getConnection();
        $sql = "SELECT * FROM audit_logs WHERE 1=1";
        $params = [];

        if (!empty($username)) {
            $sql .= " AND username LIKE :username";
            $params[':username'] = '%' . $username . '%';
        }

        if (!empty($action)) {
            $sql .= " AND action = :action";
            $params[':action'] = $action;
        }

        if (!empty($status)) {
            $sql .= " AND status = :status";
            $params[':status'] = $status;
        }

        $sql .= " ORDER BY created_at DESC LIMIT :limit OFFSET :offset";

        $stmt = $db->prepare($sql);
        foreach ($params as $key => $val) {
            $stmt->bindValue($key, $val);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }
}
