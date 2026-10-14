<?php
require "config.php";
exigir_login();

// --- Cancelar una reserva (formulario POST) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)($_POST['reserva_id'] ?? 0);

    if ($id > 0) {
        [$codigo, $res] = api_post("/api/bookings/$id/cancel");
        cortar_si_sesion_vencida($codigo);

        if ($codigo === 200 && !empty($res['success'])) {
            flash('exito', $res['message'] ?? 'Reserva cancelada.');
        } else {
            flash('error', $res['error'] ?? 'No se pudo cancelar la reserva.');
        }
    }
    // Post-Redirect-Get: al refrescar la página no se repite la cancelación
    redirigir("mis-reservas.php");
}

[$codigo, $res] = api_get('/api/bookings/my-bookings');
cortar_si_sesion_vencida($codigo);

$reservas = ($codigo === 200 && !empty($res['success'])) ? $res['data'] : [];
$error_api = ($codigo !== 200) ? ($res['error'] ?? 'No se pudo cargar tu historial.') : '';

// Las más recientes primero
usort($reservas, function ($a, $b) {
    return strcmp($b['booking_date'] . $b['start_time'], $a['booking_date'] . $a['start_time']);
});

$titulo = "Mis reservas";
$body_clase = "app";
$pagina_actual = "reservas";
include "partials/head.php";
?>
    <div class="app-shell">
        <?php include "partials/sidebar.php"; ?>

        <main class="panel">
            <header class="panel-cabecera">
                <img class="logo-panel" src="images/logo.png" alt="Slotify">
                <h1 class="panel-titulo">Historial de reservas</h1>
            </header>

            <?php mostrar_flash(); ?>

            <?php if ($error_api): ?>
                <p class="aviso aviso-error" role="alert"><?= e($error_api) ?></p>
            <?php elseif (empty($reservas)): ?>
                <p class="vacio">Todavía no tenés reservas.<br>
                    <a href="reservar.php">Reservá tu primer turno</a></p>
            <?php else: ?>
                <section class="historial">
                    <?php foreach ($reservas as $r): ?>
                        <?php [$texto_estado, $clase_estado] = estado_reserva($r); ?>
                        <article class="reserva">
                            <img src="<?= e(foto_cancha($r['court_id'])) ?>" alt="Foto de <?= e($r['court_name']) ?>">
                            <div class="datos-cancha">
                                <p class="nombre"><?= e($r['court_name']) ?></p>
                                <p><strong>Reserva:</strong> <?= e(fecha_corta($r['booking_date'])) ?> - <?= e(hora_corta($r['start_time'])) ?></p>
                                <span class="estado <?= e($clase_estado) ?>"><?= e($texto_estado) ?></span>
                                <p><strong>Tipo de cancha:</strong> <?= e($r['court_type']) ?></p>
                                <p><strong>Precio:</strong> <?= e(precio_formateado($r['price_per_hour'])) ?> / hora</p>

                                <?php if (reserva_cancelable($r)): ?>
                                    <form method="POST" action="mis-reservas.php"
                                          onsubmit="return confirm('¿Querés cancelar esta reserva?');">
                                        <input type="hidden" name="reserva_id" value="<?= e($r['id']) ?>">
                                        <button type="submit" class="boton-turquesa boton-peligro">Cancelar reserva</button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </section>
            <?php endif; ?>
        </main>
    </div>
<?php include "partials/foot.php"; ?>
