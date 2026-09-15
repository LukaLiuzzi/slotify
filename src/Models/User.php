<?php

namespace App\Models;

use App\Config\Database;
use App\Config\Security;
use PDO;

class User {
    public static function findById(int $id): ?array {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            SELECT u.id, u.username, u.email, u.role_id, r.name AS role, u.is_active, 
                   u.failed_login_attempts, u.locked_until, u.created_at, u.updated_at
            FROM users u
            INNER JOIN roles r ON u.role_id = r.id
            WHERE u.id = :id
            LIMIT 1
        ");
        $stmt->execute([':id' => $id]);
        $user = $stmt->fetch();
        return $user ?: null;
    }

    public static function findByUsername(string $username): ?array {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            SELECT u.*, r.name AS role
            FROM users u
            INNER JOIN roles r ON u.role_id = r.id
            WHERE u.username = :username
            LIMIT 1
        ");
        $stmt->execute([':username' => $username]);
        $user = $stmt->fetch();
        return $user ?: null;
    }

    public static function findByEmail(string $email): ?array {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            SELECT u.*, r.name AS role
            FROM users u
            INNER JOIN roles r ON u.role_id = r.id
            WHERE u.email = :email
            LIMIT 1
        ");
        $stmt->execute([':email' => $email]);
        $user = $stmt->fetch();
        return $user ?: null;
    }

    public static function findByUsernameOrEmail(string $identifier): ?array {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            SELECT u.*, r.name AS role
            FROM users u
            INNER JOIN roles r ON u.role_id = r.id
            WHERE u.username = :ident_username OR u.email = :ident_email
            LIMIT 1
        ");
        $stmt->execute([
            ':ident_username' => $identifier,
            ':ident_email' => $identifier
        ]);
        $user = $stmt->fetch();
        return $user ?: null;
    }

    public static function create(string $username, string $email, string $password, int $roleId = 2): int {
        $db = Database::getConnection();
        $passwordHash = password_hash($password, PASSWORD_BCRYPT);

        $stmt = $db->prepare("
            INSERT INTO users (username, email, password_hash, role_id, is_active)
            VALUES (:username, :email, :password_hash, :role_id, 1)
        ");
        $stmt->execute([
            ':username' => $username,
            ':email' => $email,
            ':password_hash' => $passwordHash,
            ':role_id' => $roleId
        ]);

        return (int)$db->lastInsertId();
    }

    public static function getAll(?string $emailFilter = null, ?string $usernameFilter = null): array {
        $db = Database::getConnection();
        $sql = "
            SELECT u.id, u.username, u.email, u.role_id, r.name AS role, u.is_active,
                   u.failed_login_attempts, u.locked_until, u.created_at
            FROM users u
            INNER JOIN roles r ON u.role_id = r.id
            WHERE 1=1
        ";
        $params = [];

        if (!empty($emailFilter)) {
            $sql .= " AND u.email LIKE :email";
            $params[':email'] = '%' . $emailFilter . '%';
        }

        if (!empty($usernameFilter)) {
            $sql .= " AND u.username LIKE :username";
            $params[':username'] = '%' . $usernameFilter . '%';
        }

        $sql .= " ORDER BY u.id DESC";

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public static function updateStatus(int $id, bool $isActive): bool {
        $db = Database::getConnection();
        $stmt = $db->prepare("UPDATE users SET is_active = :is_active WHERE id = :id");
        return $stmt->execute([
            ':is_active' => $isActive ? 1 : 0,
            ':id' => $id
        ]);
    }

    public static function unlockAccount(int $id): bool {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            UPDATE users 
            SET failed_login_attempts = 0, locked_until = NULL 
            WHERE id = :id
        ");
        return $stmt->execute([':id' => $id]);
    }

    public static function incrementFailedAttempts(int $id): int {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            UPDATE users 
            SET failed_login_attempts = failed_login_attempts + 1 
            WHERE id = :id
        ");
        $stmt->execute([':id' => $id]);

        $stmt = $db->prepare("SELECT failed_login_attempts FROM users WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $attempts = (int)$stmt->fetchColumn();

        if ($attempts >= Security::MAX_LOGIN_ATTEMPTS) {
            self::lockAccount($id, Security::LOCKOUT_MINUTES);
        }

        return $attempts;
    }

    public static function lockAccount(int $id, int $minutes): void {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            UPDATE users 
            SET locked_until = DATE_ADD(NOW(), INTERVAL :minutes MINUTE) 
            WHERE id = :id
        ");
        $stmt->execute([
            ':minutes' => $minutes,
            ':id' => $id
        ]);
    }

    public static function resetFailedAttempts(int $id): void {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            UPDATE users 
            SET failed_login_attempts = 0, locked_until = NULL 
            WHERE id = :id
        ");
        $stmt->execute([':id' => $id]);
    }

    public static function isLocked(array $user): bool {
        if (!empty($user['locked_until'])) {
            $lockTime = strtotime($user['locked_until']);
            if ($lockTime > time()) {
                return true;
            }
        }
        return false;
    }

    public static function updatePassword(int $id, string $newPassword): bool {
        $db = Database::getConnection();
        $passwordHash = password_hash($newPassword, PASSWORD_BCRYPT);
        $stmt = $db->prepare("UPDATE users SET password_hash = :password_hash WHERE id = :id");
        return $stmt->execute([
            ':password_hash' => $passwordHash,
            ':id' => $id
        ]);
    }

    public static function delete(int $id): bool {
        $db = Database::getConnection();
        $stmt = $db->prepare("DELETE FROM users WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }
}
