<?php

namespace App\Models;

use App\Config\Database;
use App\Config\Security;
use PDO;
use Exception;

class Booking {
    public static function getStandardSlots(): array {
        $slots = [];
        for ($h = Security::OPENING_HOUR; $h < Security::CLOSING_HOUR; $h++) {
            $start = sprintf('%02d:00:00', $h);
            $end = sprintf('%02d:00:00', $h + 1);
            $slots[] = [
                'start_time' => $start,
                'end_time' => $end,
                'label' => sprintf('%02d:00 - %02d:00', $h, $h + 1)
            ];
        }
        return $slots;
    }

    public static function getAvailableSlots(int $courtId, string $date): array {
        $db = Database::getConnection();
        $slots = self::getStandardSlots();

        $stmt = $db->prepare("
            SELECT start_time, end_time, user_id, status 
            FROM bookings 
            WHERE court_id = :court_id 
              AND booking_date = :booking_date 
              AND status = 'confirmada'
        ");
        $stmt->execute([
            ':court_id' => $courtId,
            ':booking_date' => $date
        ]);
        $booked = $stmt->fetchAll();

        $bookedTimes = [];
        foreach ($booked as $b) {
            $bookedTimes[$b['start_time']] = true;
        }

        $result = [];
        foreach ($slots as $slot) {
            $isBooked = isset($bookedTimes[$slot['start_time']]);
            $result[] = [
                'start_time' => $slot['start_time'],
                'end_time' => $slot['end_time'],
                'label' => $slot['label'],
                'is_available' => !$isBooked
            ];
        }

        return $result;
    }

    public static function create(int $courtId, int $userId, string $date, string $startTime, string $endTime): int {
        $db = Database::getConnection();

        try {
            $db->beginTransaction();

            $stmt = $db->prepare("
                SELECT id 
                FROM bookings 
                WHERE court_id = :court_id 
                  AND booking_date = :booking_date 
                  AND status = 'confirmada'
                  AND (start_time < :end_time AND end_time > :start_time)
                FOR UPDATE
            ");
            $stmt->execute([
                ':court_id' => $courtId,
                ':booking_date' => $date,
                ':start_time' => $startTime,
                ':end_time' => $endTime
            ]);

            if ($stmt->fetch()) {
                $db->rollBack();
                throw new Exception('El turno seleccionado ya se encuentra reservado');
            }

            $stmt = $db->prepare("
                INSERT INTO bookings (court_id, user_id, booking_date, start_time, end_time, status)
                VALUES (:court_id, :user_id, :booking_date, :start_time, :end_time, 'confirmada')
            ");
            $stmt->execute([
                ':court_id' => $courtId,
                ':user_id' => $userId,
                ':booking_date' => $date,
                ':start_time' => $startTime,
                ':end_time' => $endTime
            ]);

            $bookingId = (int)$db->lastInsertId();
            $db->commit();
            return $bookingId;
        } catch (Exception $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            throw $e;
        }
    }

    public static function findById(int $id): ?array {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            SELECT b.*, c.name AS court_name, c.price_per_hour, u.username, u.email
            FROM bookings b
            INNER JOIN courts c ON b.court_id = c.id
            INNER JOIN users u ON b.user_id = u.id
            WHERE b.id = :id
            LIMIT 1
        ");
        $stmt->execute([':id' => $id]);
        $res = $stmt->fetch();
        return $res ?: null;
    }

    public static function getByUser(int $userId): array {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            SELECT b.*, c.name AS court_name, c.court_type, c.price_per_hour
            FROM bookings b
            INNER JOIN courts c ON b.court_id = c.id
            WHERE b.user_id = :user_id
            ORDER BY b.booking_date DESC, b.start_time DESC
        ");
        $stmt->execute([':user_id' => $userId]);
        return $stmt->fetchAll();
    }

    public static function cancel(int $id, int $userId, bool $isAdmin = false): bool {
        $db = Database::getConnection();
        $booking = self::findById($id);

        if (!$booking) {
            throw new Exception('Reserva no encontrada');
        }

        if (!$isAdmin && (int)$booking['user_id'] !== $userId) {
            throw new Exception('No tiene permisos para cancelar esta reserva');
        }

        if ($booking['status'] === 'cancelada') {
            throw new Exception('La reserva ya se encuentra cancelada');
        }

        if (!$isAdmin) {
            $bookingTimestamp = strtotime($booking['booking_date'] . ' ' . $booking['start_time']);
            if ($bookingTimestamp <= time()) {
                throw new Exception('No se puede cancelar una reserva pasada o en curso');
            }
        }

        $stmt = $db->prepare("UPDATE bookings SET status = 'cancelada' WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }

    public static function getAll(?string $date = null, ?int $courtId = null, ?string $status = null): array {
        $db = Database::getConnection();
        $sql = "
            SELECT b.*, c.name AS court_name, c.court_type, c.price_per_hour, u.username, u.email
            FROM bookings b
            INNER JOIN courts c ON b.court_id = c.id
            INNER JOIN users u ON b.user_id = u.id
            WHERE 1=1
        ";
        $params = [];

        if (!empty($date)) {
            $sql .= " AND b.booking_date = :date";
            $params[':date'] = $date;
        }

        if (!empty($courtId)) {
            $sql .= " AND b.court_id = :court_id";
            $params[':court_id'] = $courtId;
        }

        if (!empty($status)) {
            $sql .= " AND b.status = :status";
            $params[':status'] = $status;
        }

        $sql .= " ORDER BY b.booking_date DESC, b.start_time DESC";

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public static function updateStatus(int $id, string $status): bool {
        $db = Database::getConnection();
        $stmt = $db->prepare("UPDATE bookings SET status = :status WHERE id = :id");
        return $stmt->execute([
            ':status' => $status,
            ':id' => $id
        ]);
    }
}
