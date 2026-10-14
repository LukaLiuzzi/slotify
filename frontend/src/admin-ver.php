<?php
require "config.php";
require_once "admin_helpers.php";
exigir_admin();

// Detalle de solo lectura: ?tipo=cancha|reserva|usuario&id=N
$tipo = $_GET['tipo'] ?? '';
$id = (int)($_GET['id'] ?? 0);
$mapa = ['cancha' => 'canchas', 'reserva' => 'reservas', 'usuario' => 'usuarios'];
if (!isset($mapa[$tipo]) || $id <= 0) {
    redirigir('admin.php');
}
$seccion = $mapa[$tipo];

$fila = null;
if ($tipo === 'cancha') {
    [$codigo, $res] = api_get("/api/courts/$id");
    if ($codigo === 200 && !empty($res['success'])) { $fila = $res['data']; }
} else {
    $ruta = ($tipo === 'reserva') ? '/api/admin/bookings' : '/api/admin/users';
    [$codigo, $res] = api_get($ruta);
    cortar_si_sesion_vencida($codigo);
    if ($codigo === 200 && !empty($res['success'])) { $fila = buscar_por_id($res['data'], $id); }
}

if ($fila === null) {
    flash('error', 'No se encontró el registro.');
    redirigir('admin.php?seccion=' . $seccion);
}

// Pares etiqueta => valor, según el tipo
if ($tipo === 'cancha') {
    $titulo = 'Cancha ' . id_formateado('C', $id);
    $datos = [
        'ID' => id_formateado('C', $id),
        'Nombre' => $fila['name'],
        'Tipo de cancha' => $fila['court_type'],
        'Precio por hora' => precio_formateado($fila['price_per_hour']),
        'Estado' => estado_cancha($fila),
        'Creada' => $fila['created_at'] ?? '-',
    ];
    $editar = 'admin-cancha.php?id=' . $id;
} elseif ($tipo === 'reserva') {
    $titulo = 'Reserva ' . id_formateado('R', $id);
    $datos = [
        'ID' => id_formateado('R', $id),
        'Usuario' => $fila['username'] ?? '-',
        'Cancha' => $fila['court_name'] ?? '-',
        'Fecha' => fecha_corta($fila['booking_date']),
        'Horario' => hora_corta($fila['start_time']) . (isset($fila['end_time']) ? ' a ' . hora_corta($fila['end_time']) : ''),
        'Estado' => estado_reserva_admin($fila),
    ];
    $editar = 'admin-editar.php?tipo=reserva&id=' . $id;
} else {
    $titulo = 'Usuario ' . id_formateado('U', $id);
    $datos = [
        'ID' => id_formateado('U', $id),
        'Nombre de usuario' => $fila['username'],
        'Correo electrónico' => $fila['email'] ?? '-',
        'Tipo de cuenta' => ($fila['role'] ?? '') === 'admin' ? 'Administrador' : 'Jugador',
        'Estado' => estado_usuario($fila),
        'Creado' => $fila['created_at'] ?? '-',
    ];
    $editar = 'admin-editar.php?tipo=usuario&id=' . $id;
}

$body_clase = "app";
$pagina_actual = "admin-" . $seccion;
include "partials/head.php";
?>
    <div class="app-shell shell-admin">
        <?php include "partials/sidebar.php"; ?>

        <main class="panel">
            <header class="panel-cabecera">
                <img class="logo-panel" src="images/logo.png" alt="Slotify">
                <h1 class="panel-titulo"><?= e($titulo) ?></h1>
            </header>

            <section class="datos-usuario">
                <h2>Detalle</h2>
                <dl>
                    <?php foreach ($datos as $etiqueta => $valor): ?>
                        <dt><?= e($etiqueta) ?>:</dt>
                        <dd><?= e($valor) ?></dd>
                    <?php endforeach; ?>
                </dl>
            </section>

            <div class="botones barra-seccion">
                <a class="boton-turquesa" href="<?= e($editar) ?>">Editar</a>
                <a class="boton-turquesa boton-peligro" href="admin.php?seccion=<?= e($seccion) ?>">Volver al listado</a>
            </div>
        </main>
    </div>
<?php include "partials/foot.php"; ?>
