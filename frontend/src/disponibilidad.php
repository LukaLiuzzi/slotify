<?php
require "config.php";
exigir_login();

$court_id = (int)($_GET['court_id'] ?? $_POST['court_id'] ?? 0);
if ($court_id <= 0) {
    redirigir("home.php");
}

// --- Reservar un turno (formulario POST) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fecha_post = $_POST['booking_date'] ?? date('Y-m-d');

    [$codigo, $res] = api_post('/api/bookings', [
        'court_id'     => $court_id,
        'booking_date' => $fecha_post,
        'start_time'   => $_POST['start_time'] ?? '',
        'end_time'     => $_POST['end_time'] ?? '',
    ]);
    cortar_si_sesion_vencida($codigo);

    if ($codigo === 201 && !empty($res['success'])) {
        flash('exito', '¡Turno reservado! ' . fecha_corta($fecha_post) . ' a las ' . hora_corta($_POST['start_time'] ?? '') . ' hs.');
    } else {
        // 409 = otra persona reservó ese horario primero
        flash('error', $res['error'] ?? 'No se pudo reservar el turno.');
    }

    // Post-Redirect-Get: evita reservar dos veces si se refresca la página
    redirigir("disponibilidad.php?court_id=$court_id&date=" . urlencode($fecha_post));
}

$fecha = $_GET['date'] ?? date('Y-m-d');
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
    $fecha = date('Y-m-d');
}

[$codigo_cancha, $res_cancha] = api_get("/api/courts/$court_id");
[$codigo_disp, $res_disp] = api_get("/api/bookings/availability?court_id=$court_id&date=" . urlencode($fecha));

$cancha = ($codigo_cancha === 200 && !empty($res_cancha['success'])) ? $res_cancha['data'] : null;
$slots = ($codigo_disp === 200 && !empty($res_disp['success'])) ? $res_disp['data']['slots'] : [];
$error_api = (!$cancha || $codigo_disp !== 200)
    ? ($res_cancha['error'] ?? $res_disp['error'] ?? 'No se pudo cargar la disponibilidad.')
    : '';

$titulo = "Disponibilidad";
$body_clase = "app";
$pagina_actual = "disponibilidad";
include "partials/head.php";
?>
    <div class="app-shell">
        <?php include "partials/sidebar.php"; ?>

        <main class="panel">
            <header class="panel-cabecera">
                <img class="logo-panel" src="images/logo.png" alt="Slotify">
                <h1 class="panel-titulo">Disponibilidad</h1>
            </header>

            <?php mostrar_flash(); ?>

            <?php if ($error_api): ?>
                <p class="aviso aviso-error" role="alert"><?= e($error_api) ?></p>
                <p><a href="reservar.php">Volver a la lista de canchas</a></p>
            <?php else: ?>
                <h2 class="encabezado-cancha"><?= e($cancha['name']) ?></h2>
                <p><?= e($cancha['court_type']) ?> · <?= e(precio_formateado($cancha['price_per_hour'])) ?> / hora</p>

                <!-- Al elegir otra fecha se recarga la página con ?date=... -->
                <form class="selector-fecha" method="GET" action="disponibilidad.php">
                    <input type="hidden" name="court_id" value="<?= e($court_id) ?>">
                    <label for="fecha">Elegí una fecha</label>
                    <input class="campo-app" type="date" id="fecha" name="date" value="<?= e($fecha) ?>"
                           min="<?= e(date('Y-m-d')) ?>" onchange="this.form.submit()">
                </form>

                <?php if (empty($slots)): ?>
                    <p class="vacio">No hay turnos para esta fecha.</p>
                <?php endif; ?>

                <div class="lista-slots">
                    <?php foreach ($slots as $slot): ?>
                        <?php if (!empty($slot['is_available'])): ?>
                            <form method="POST" action="disponibilidad.php" class="slot">
                                <input type="hidden" name="court_id" value="<?= e($court_id) ?>">
                                <input type="hidden" name="booking_date" value="<?= e($fecha) ?>">
                                <input type="hidden" name="start_time" value="<?= e($slot['start_time']) ?>">
                                <input type="hidden" name="end_time" value="<?= e($slot['end_time']) ?>">
                                <span><?= e($slot['label']) ?></span>
                                <button type="submit" class="boton-turquesa">Reservar</button>
                            </form>
                        <?php else: ?>
                            <div class="slot slot-ocupado">
                                <span><?= e($slot['label']) ?></span>
                                <span>Ocupado</span>
                            </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </main>
    </div>
<?php include "partials/foot.php"; ?>
