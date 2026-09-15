<?php

spl_autoload_register(function (string $class): void {
    $prefix = 'App\\';
    $baseDir = __DIR__ . '/';
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }
    $relativeClass = substr($class, $len);
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});

use App\Core\Request;
use App\Core\Router;
use App\Middleware\CorsMiddleware;
use App\Middleware\AuthMiddleware;
use App\Middleware\AdminMiddleware;
use App\Controllers\AuthController;
use App\Controllers\UserController;
use App\Controllers\AuditController;
use App\Controllers\CourtController;
use App\Controllers\BookingController;

$request = new Request();
(new CorsMiddleware())->handle($request);

$router = new Router();

$router->get('/api/csrf-token', [AuthController::class, 'csrfToken']);
$router->post('/api/auth/register', [AuthController::class, 'register']);
$router->post('/api/auth/login', [AuthController::class, 'login']);
$router->post('/api/auth/logout', [AuthController::class, 'logout']);
$router->post('/api/auth/forgot-password', [AuthController::class, 'forgotPassword']);
$router->post('/api/auth/reset-password', [AuthController::class, 'resetPassword']);

$router->get('/api/courts', [CourtController::class, 'index']);
$router->get('/api/courts/{id}', [CourtController::class, 'show']);
$router->get('/api/bookings/availability', [BookingController::class, 'availability']);

$router->get('/api/auth/me', [AuthController::class, 'me'], [AuthMiddleware::class]);
$router->post('/api/bookings', [BookingController::class, 'store'], [AuthMiddleware::class]);
$router->get('/api/bookings/my-bookings', [BookingController::class, 'myBookings'], [AuthMiddleware::class]);
$router->post('/api/bookings/{id}/cancel', [BookingController::class, 'cancel'], [AuthMiddleware::class]);

$router->get('/api/admin/users', [UserController::class, 'index'], [AdminMiddleware::class]);
$router->put('/api/admin/users/{id}/status', [UserController::class, 'toggleStatus'], [AdminMiddleware::class]);
$router->delete('/api/admin/users/{id}', [UserController::class, 'delete'], [AdminMiddleware::class]);
$router->get('/api/admin/audit-logs', [AuditController::class, 'index'], [AdminMiddleware::class]);
$router->post('/api/admin/courts', [CourtController::class, 'store'], [AdminMiddleware::class]);
$router->put('/api/admin/courts/{id}', [CourtController::class, 'update'], [AdminMiddleware::class]);
$router->delete('/api/admin/courts/{id}', [CourtController::class, 'delete'], [AdminMiddleware::class]);
$router->get('/api/admin/bookings', [BookingController::class, 'adminIndex'], [AdminMiddleware::class]);
$router->post('/api/admin/bookings/{id}/status', [BookingController::class, 'adminChangeStatus'], [AdminMiddleware::class]);

$router->dispatch($request);
