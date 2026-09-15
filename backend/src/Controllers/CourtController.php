<?php

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Models\Court;
use App\Models\AuditLog;

class CourtController {
    public function index(Request $request): void {
        Session::start();
        $isAdmin = Session::get('role') === 'admin';
        $onlyActive = !$isAdmin || ($request->get('active_only') === '1');

        $courts = Court::getAll($onlyActive);
        Response::success($courts);
    }

    public function show(Request $request, array $params): void {
        $id = (int)($params['id'] ?? 0);
        $court = Court::findById($id);

        if (!$court) {
            Response::error('Cancha no encontrada', 404);
        }

        Response::success($court);
    }

    public function store(Request $request): void {
        $name = trim((string)$request->get('name', ''));
        $courtType = trim((string)$request->get('court_type', ''));
        $pricePerHour = (float)$request->get('price_per_hour', 0);
        $isActive = $request->get('is_active') !== null ? (bool)$request->get('is_active') : true;

        if (empty($name) || empty($courtType) || $pricePerHour <= 0) {
            Response::error('Datos inválidos. Se requiere name, court_type y price_per_hour positivo', 400);
        }

        $courtId = Court::create($name, $courtType, $pricePerHour, $isActive);

        $adminId = Session::get('user_id');
        $adminUsername = Session::get('username');
        AuditLog::log(
            $adminId,
            $adminUsername,
            'ADMIN_CREATE_COURT',
            $request->getIpAddress(),
            $request->getUserAgent(),
            'success',
            "Cancha '{$name}' (ID {$courtId}) creada"
        );

        $court = Court::findById($courtId);
        Response::success($court, 'Cancha creada exitosamente', 201);
    }

    public function update(Request $request, array $params): void {
        $id = (int)($params['id'] ?? 0);
        $court = Court::findById($id);

        if (!$court) {
            Response::error('Cancha no encontrada', 404);
        }

        $name = trim((string)$request->get('name', $court['name']));
        $courtType = trim((string)$request->get('court_type', $court['court_type']));
        $pricePerHour = $request->get('price_per_hour') !== null ? (float)$request->get('price_per_hour') : (float)$court['price_per_hour'];
        $isActive = $request->get('is_active') !== null ? (bool)$request->get('is_active') : (bool)$court['is_active'];

        if (empty($name) || empty($courtType) || $pricePerHour <= 0) {
            Response::error('Datos inválidos', 400);
        }

        Court::update($id, $name, $courtType, $pricePerHour, $isActive);

        $adminId = Session::get('user_id');
        $adminUsername = Session::get('username');
        AuditLog::log(
            $adminId,
            $adminUsername,
            'ADMIN_UPDATE_COURT',
            $request->getIpAddress(),
            $request->getUserAgent(),
            'success',
            "Cancha ID {$id} actualizada"
        );

        $updated = Court::findById($id);
        Response::success($updated, 'Cancha actualizada exitosamente');
    }

    public function delete(Request $request, array $params): void {
        $id = (int)($params['id'] ?? 0);
        $court = Court::findById($id);

        if (!$court) {
            Response::error('Cancha no encontrada', 404);
        }

        Court::delete($id);

        $adminId = Session::get('user_id');
        $adminUsername = Session::get('username');
        AuditLog::log(
            $adminId,
            $adminUsername,
            'ADMIN_DELETE_COURT',
            $request->getIpAddress(),
            $request->getUserAgent(),
            'success',
            "Cancha ID {$id} dada de baja"
        );

        Response::success(null, 'Cancha dada de baja exitosamente');
    }
}
