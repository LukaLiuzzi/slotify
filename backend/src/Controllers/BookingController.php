<?php

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\Validator;
use App\Models\Booking;
use App\Models\Court;
use App\Models\AuditLog;
use Exception;

class BookingController {
    public function availability(Request $request): void {
        $courtId = (int)$request->get('court_id', 0);
        $date = (string)$request->get('date', '');

        if ($courtId <= 0 || empty($date)) {
            Response::error('court_id y date (YYYY-MM-DD) son requeridos', 400);
        }

        if (!Validator::validateDate($date)) {
            Response::error('Formato de fecha inválido. Utilice YYYY-MM-DD', 400);
        }

        $court = Court::findById($courtId);
        if (!$court || (int)$court['is_active'] === 0) {
            Response::error('Cancha no disponible o inexistente', 404);
        }

        $slots = Booking::getAvailableSlots($courtId, $date);

        Response::success([
            'court' => [
                'id' => (int)$court['id'],
                'name' => $court['name'],
                'price_per_hour' => (float)$court['price_per_hour']
            ],
            'date' => $date,
            'slots' => $slots
        ]);
    }

    public function store(Request $request): void {
        $courtId = (int)$request->get('court_id', 0);
        $date = (string)$request->get('booking_date', '');
        $startTime = (string)$request->get('start_time', '');
        $endTime = (string)$request->get('end_time', '');

        if ($courtId <= 0 || empty($date) || empty($startTime) || empty($endTime)) {
            Response::error('Campos requeridos: court_id, booking_date, start_time y end_time', 400);
        }

        if (!Validator::validateDate($date)) {
            Response::error('Formato de fecha inválido. Utilice YYYY-MM-DD', 400);
        }

        if (strlen($startTime) === 5) {
            $startTime .= ':00';
        }
        if (strlen($endTime) === 5) {
            $endTime .= ':00';
        }

        if (!Validator::validateTime($startTime) || !Validator::validateTime($endTime)) {
            Response::error('Formato de hora inválido. Utilice HH:MM o HH:MM:SS', 400);
        }

        if (strtotime($date . ' ' . $startTime) <= time()) {
            Response::error('No se pueden reservar turnos en fechas u horas pasadas', 400);
        }

        if (strtotime($endTime) <= strtotime($startTime)) {
            Response::error('La hora de fin debe ser posterior a la hora de inicio', 400);
        }

        $court = Court::findById($courtId);
        if (!$court || (int)$court['is_active'] === 0) {
            Response::error('La cancha seleccionada no está disponible', 404);
        }

        $userId = (int)Session::get('user_id');
        $username = Session::get('username');

        try {
            $bookingId = Booking::create($courtId, $userId, $date, $startTime, $endTime);

            AuditLog::log(
                $userId,
                $username,
                'CREATE_BOOKING',
                $request->getIpAddress(),
                $request->getUserAgent(),
                'success',
                "Reserva creada ID {$bookingId} en cancha {$court['name']} para fecha {$date} {$startTime}-{$endTime}"
            );

            $booking = Booking::findById($bookingId);
            Response::success($booking, 'Turno reservado exitosamente', 201);
        } catch (Exception $e) {
            AuditLog::log(
                $userId,
                $username,
                'CREATE_BOOKING_FAILED',
                $request->getIpAddress(),
                $request->getUserAgent(),
                'failure',
                $e->getMessage()
            );
            Response::error($e->getMessage(), 409);
        }
    }

    public function myBookings(Request $request): void {
        $userId = (int)Session::get('user_id');
        $bookings = Booking::getByUser($userId);
        Response::success($bookings);
    }

    public function cancel(Request $request, array $params): void {
        $id = (int)($params['id'] ?? 0);
        $userId = (int)Session::get('user_id');
        $username = Session::get('username');
        $isAdmin = Session::get('role') === 'admin';

        try {
            Booking::cancel($id, $userId, $isAdmin);

            AuditLog::log(
                $userId,
                $username,
                'CANCEL_BOOKING',
                $request->getIpAddress(),
                $request->getUserAgent(),
                'success',
                "Reserva ID {$id} cancelada"
            );

            Response::success(null, 'Reserva cancelada correctamente');
        } catch (Exception $e) {
            Response::error($e->getMessage(), 400);
        }
    }

    public function adminIndex(Request $request): void {
        $date = $request->get('date');
        $courtId = $request->get('court_id') ? (int)$request->get('court_id') : null;
        $status = $request->get('status');

        $bookings = Booking::getAll($date, $courtId, $status);
        Response::success($bookings);
    }

    public function adminChangeStatus(Request $request, array $params): void {
        $id = (int)($params['id'] ?? 0);
        $status = (string)$request->get('status', '');

        if (!in_array($status, ['confirmada', 'cancelada'], true)) {
            Response::error("Estado inválido. Valores permitidos: 'confirmada', 'cancelada'", 400);
        }

        $booking = Booking::findById($id);
        if (!$booking) {
            Response::error('Reserva no encontrada', 404);
        }

        Booking::updateStatus($id, $status);

        $adminId = (int)Session::get('user_id');
        $adminUsername = Session::get('username');

        AuditLog::log(
            $adminId,
            $adminUsername,
            'ADMIN_UPDATE_BOOKING_STATUS',
            $request->getIpAddress(),
            $request->getUserAgent(),
            'success',
            "Estado de reserva ID {$id} modificado a {$status}"
        );

        $updated = Booking::findById($id);
        Response::success($updated, 'Estado de la reserva actualizado');
    }
}
