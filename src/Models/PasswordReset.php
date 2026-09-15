<?php

namespace App\Models;

use App\Config\Database;
use App\Config\Security;
use PDO;

class PasswordReset {
    public static function createToken(int $userId): string {
        $db = Database::getConnection();
        $plainToken = bin2hex(random_bytes(32));
        $tokenHash = hash('sha256', $plainToken);

        $hours = Security::RESET_TOKEN_LIFETIME_HOURS;
        $stmt = $db->prepare("
            INSERT INTO password_resets (user_id, token_hash, expires_at, used)
            VALUES (:user_id, :token_hash, DATE_ADD(NOW(), INTERVAL :hours HOUR), 0)
        ");
        $stmt->execute([
            ':user_id' => $userId,
            ':token_hash' => $tokenHash,
            ':hours' => $hours
        ]);

        return $plainToken;
    }

    public static function verifyToken(string $plainToken): ?array {
        $db = Database::getConnection();
        $tokenHash = hash('sha256', $plainToken);

        $stmt = $db->prepare("
            SELECT * FROM password_resets
            WHERE token_hash = :token_hash 
              AND used = 0 
              AND expires_at > NOW()
            ORDER BY id DESC
            LIMIT 1
        ");
        $stmt->execute([':token_hash' => $tokenHash]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function markAsUsed(int $id): bool {
        $db = Database::getConnection();
        $stmt = $db->prepare("UPDATE password_resets SET used = 1 WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }
}
