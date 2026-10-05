<?php
require "config.php";
exigir_login();

$court_id = $_GET['court_id'] ?? $_POST['court_id'] ?? null;
if (!$court_id) {
    redirigir("home.php");
}

// --- Si llega una reserva (POST), la procesamos ANTES de mostrar nada ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    [$codigo, $res] = api_post('/api/bookings', [
        'court_id'     => (int)$court_id,
        'booking_date' => $_POST['booking_date'],
        'start_time'   => $_POST['start_time'],
        'end_time'     => $_POST['end_time'],
    ]);

    if ($codigo === 201 && !empty($res['success'])) {
        $_SESSION['flash_exito'] = "¡Turno reservado! " . ($_POST['start_time'] ?? '') . " hs";
    } elseif ($codigo === 409) {
        $_SESSION['flash_error'] = $res['error'] ?? "Ese turno ya fue reservado por otra persona.";
    } else {
        $_SESSION['flash_error'] = $res['error'] ?? "No se pudo reservar el turno.";
    }

    // Patrón Post-Redirect-Get: evita que al recargar se vuelva a enviar el formulario
    $fecha_post = $_POST['booking_date'];
    redirigir("disponibilidad.php?court_id=$court_id&date=$fecha_post");
}

$fecha = $_GET['date'] ?? date('Y-m-d');

// Traemos el detalle de la cancha y la disponibilidad del día elegido
[$codigo_cancha, $res_cancha] = api_get("/api/courts/$court_id");
[$codigo_disp, $res_disp] = api_get("/api/bookings/availability?court_id=$court_id&date=$fecha");

$cancha = ($codigo_cancha === 200) ? $res_cancha['data'] : null;
$slots = ($codigo_disp === 200) ? $res_disp['data']['slots'] : [];
$error_api = ($codigo_cancha !== 200 || $codigo_disp !== 200)
    ? ($res_cancha['error'] ?? $res_disp['error'] ?? 'No se pudo cargar la disponibilidad.')
    : '';

$flash_exito = $_SESSION['flash_exito'] ?? '';
$flash_error = $_SESSION['flash_error'] ?? '';
unset($_SESSION['flash_exito'], $_SESSION['flash_error']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Slotify - Disponibilidad</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body style="position:relative;">
    <a href="home.php" class="volver">Volver al inicio</a>

    <div class="tarjeta" style="max-width:520px;">
        <img src="images/logo.png" alt="Slotify" class="logo-img logo-img-chico">

        <?php if ($cancha): ?>
            <p class="lema" style="margin-bottom:4px;"><?= htmlspecialchars($cancha['name']) ?></p>
            <p style="color:#0d3b52;">
                <?= htmlspecialchars($cancha['court_type']) ?> ·
                $<?= number_format((float)$cancha['price_per_hour'], 0, ',', '.') ?> / hora
            </p>
        <?php endif; ?>

        <!-- Selector de fecha: al cambiar, recarga la página con ?date=... -->
        <form method="GET" action="disponibilidad.php" style="margin:18px 0;">
            <input type="hidden" name="court_id" value="<?= htmlspecialchars($court_id) ?>">
            <label class="etiqueta">Elegí una fecha</label>
            <input type="date" name="date" value="<?= htmlspecialchars($fecha) ?>"
                   onchange="this.form.submit()">
        </form>

        <?php if ($flash_exito): ?>
            <p class="exito"><?= htmlspecialchars($flash_exito) ?></p>
        <?php endif; ?>
        <?php if ($flash_error): ?>
            <p class="error"><?= htmlspecialchars($flash_error) ?></p>
        <?php endif; ?>
        <?php if ($error_api): ?>
            <p class="error"><?= htmlspecialchars($error_api) ?></p>
        <?php endif; ?>

        <?php if (!$error_api): ?>
            <div class="lista-slots">
                <?php foreach ($slots as $slot): ?>
                    <?php if ($slot['is_available']): ?>
                        <form method="POST" action="disponibilidad.php" class="slot">
                            <input type="hidden" name="court_id" value="<?= htmlspecialchars($court_id) ?>">
                            <input type="hidden" name="booking_date" value="<?= htmlspecialchars($fecha) ?>">
                            <input type="hidden" name="start_time" value="<?= htmlspecialchars($slot['start_time']) ?>">
                            <input type="hidden" name="end_time" value="<?= htmlspecialchars($slot['end_time']) ?>">
                            <span><?= htmlspecialchars($slot['label']) ?></span>
                            <button type="submit" class="boton-chico">Reservar</button>
                        </form>
                    <?php else: ?>
                        <div class="slot slot-ocupado">
                            <span><?= htmlspecialchars($slot['label']) ?></span>
                            <span>Ocupado</span>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
