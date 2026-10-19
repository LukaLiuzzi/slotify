<?php

namespace App\Models;

use App\Config\Database;
use PDO;

class PasswordChangeRequest {
    public static function create(int $userId): bool {
        $db = Database::getConnection();

        if (self::hasPending($userId)) {
            return false;
        }

        $stmt = $db->prepare("
            INSERT INTO password_change_requests (user_id, status)
            VALUES (:user_id, 'pending')
        ");
        $stmt->execute([':user_id' => $userId]);
        return true;
    }

    public static function hasPending(int $userId): bool {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            SELECT COUNT(*) FROM password_change_requests
            WHERE user_id = :user_id AND status = 'pending'
        ");
        $stmt->execute([':user_id' => $userId]);
        return (int)$stmt->fetchColumn() > 0;
    }

    public static function getPending(): array {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            SELECT r.id, r.user_id, r.requested_at,
                   u.username, u.email, u.is_active, u.locked_until, u.failed_login_attempts
            FROM password_change_requests r
            INNER JOIN users u ON r.user_id = u.id
            WHERE r.status = 'pending'
            ORDER BY r.requested_at ASC
        ");
        $stmt->execute();
        $rows = $stmt->fetchAll();
        return $rows ?: [];
    }

    public static function findPending(int $id): ?array {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            SELECT id, user_id FROM password_change_requests
            WHERE id = :id AND status = 'pending'
            LIMIT 1
        ");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function resolve(int $id, int $resolvedBy): bool {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            UPDATE password_change_requests
            SET status = 'resolved', resolved_at = NOW(), resolved_by = :resolved_by
            WHERE id = :id
        ");
        return $stmt->execute([':id' => $id, ':resolved_by' => $resolvedBy]);
    }

    public static function reject(int $id, int $resolvedBy): bool {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            UPDATE password_change_requests
            SET status = 'rejected', resolved_at = NOW(), resolved_by = :resolved_by
            WHERE id = :id AND status = 'pending'
        ");
        return $stmt->execute([':id' => $id, ':resolved_by' => $resolvedBy]);
    }
}
