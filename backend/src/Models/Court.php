<?php

namespace App\Models;

use App\Config\Database;
use PDO;

class Court {
    public static function getAll(bool $onlyActive = true): array {
        $db = Database::getConnection();
        $sql = "SELECT * FROM courts";
        if ($onlyActive) {
            $sql .= " WHERE is_active = 1";
        }
        $sql .= " ORDER BY id ASC";

        $stmt = $db->query($sql);
        return $stmt->fetchAll();
    }

    public static function findById(int $id): ?array {
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT * FROM courts WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $id]);
        $court = $stmt->fetch();
        return $court ?: null;
    }

    public static function create(string $name, string $courtType, float $pricePerHour, bool $isActive = true): int {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            INSERT INTO courts (name, court_type, price_per_hour, is_active)
            VALUES (:name, :court_type, :price_per_hour, :is_active)
        ");
        $stmt->execute([
            ':name' => $name,
            ':court_type' => $courtType,
            ':price_per_hour' => $pricePerHour,
            ':is_active' => $isActive ? 1 : 0
        ]);

        return (int)$db->lastInsertId();
    }

    public static function update(int $id, string $name, string $courtType, float $pricePerHour, bool $isActive): bool {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            UPDATE courts 
            SET name = :name, court_type = :court_type, price_per_hour = :price_per_hour, is_active = :is_active
            WHERE id = :id
        ");
        return $stmt->execute([
            ':name' => $name,
            ':court_type' => $courtType,
            ':price_per_hour' => $pricePerHour,
            ':is_active' => $isActive ? 1 : 0,
            ':id' => $id
        ]);
    }

    public static function delete(int $id): bool {
        $db = Database::getConnection();
        $stmt = $db->prepare("UPDATE courts SET is_active = 0 WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }
}
