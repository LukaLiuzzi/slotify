<?php
require "config.php";
require_once "admin_helpers.php";
exigir_admin();

$secciones = ['canchas', 'reservas', 'usuarios', 'cambios'];
$seccion = $_GET['seccion'] ?? $_POST['seccion'] ?? '';
if (!in_array($seccion, $secciones, true)) {
    $seccion = '';
}
$destino = 'admin.php' . ($seccion !== '' ? '?seccion=' . $seccion : '');

// --- Acciones (el botón "borrar" de cada fila envía un formulario POST) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';
    $id = (int)($_POST['id'] ?? 0);

    if ($id > 0 && $accion === 'eliminar_usuario') {
        [$codigo, $res] = api_delete("/api/admin/users/$id");
    } elseif ($id > 0 && $accion === 'eliminar_cancha') {
        [$codigo, $res] = api_delete("/api/admin/courts/$id");
    } elseif ($id > 0 && $accion === 'cancelar_reserva') {
        [$codigo, $res] = api_post("/api/admin/bookings/$id/status", ['status' => 'cancelada']);
    } elseif ($id > 0 && $accion === 'cambiar_password') {
        [$codigo, $res] = api_post("/api/admin/password-requests/$id/password", ['password' => (string)($_POST['password'] ?? '')]);
    } elseif ($id > 0 && $accion === 'descartar_solicitud') {
        [$codigo, $res] = api_post("/api/admin/password-requests/$id/reject");
    } else {
        redirigir($destino);
    }

    cortar_si_sesion_vencida($codigo);

    if ($codigo >= 200 && $codigo < 300 && !empty($res['success'])) {
        flash('exito', $res['message'] ?? 'Listo.');
    } else {
        flash('error', $res['error'] ?? 'No se pudo completar la acción.');
    }
    redirigir($destino);
}

// --- Datos ---
$email_buscado = trim($_GET['email'] ?? '');

[$c_reservas, $r_reservas] = api_get('/api/admin/bookings');
cortar_si_sesion_vencida($c_reservas);

[$c_solicitudes_pass, $r_solicitudes_pass] = api_get('/api/admin/password-requests');
cortar_si_sesion_vencida($c_solicitudes_pass);

$ruta_usuarios = '/api/admin/users';
if ($seccion === 'usuarios' && $email_buscado !== '') {
    $ruta_usuarios .= '?email=' . urlencode($email_buscado);
}
[$c_usuarios, $r_usuarios] = api_get($ruta_usuarios);
[$c_canchas, $r_canchas] = api_get('/api/courts?active_only=0');

$reservas = ($c_reservas === 200 && !empty($r_reservas['success'])) ? $r_reservas['data'] : [];
$usuarios = ($c_usuarios === 200 && !empty($r_usuarios['success'])) ? $r_usuarios['data'] : [];
$canchas = ($c_canchas === 200 && !empty($r_canchas['success'])) ? $r_canchas['data'] : [];

// Lo más nuevo primero
usort($reservas, function ($a, $b) {
    return (int)$b['id'] <=> (int)$a['id'];
});
usort($usuarios, function ($a, $b) {
    return (int)$b['id'] <=> (int)$a['id'];
});
usort($canchas, function ($a, $b) {
    return (int)$b['id'] <=> (int)$a['id'];
});

$errores = [];
if ($c_reservas !== 200) { $errores[] = $r_reservas['error'] ?? 'No se pudieron cargar las reservas.'; }
if ($c_usuarios !== 200) { $errores[] = $r_usuarios['error'] ?? 'No se pudieron cargar los usuarios.'; }
if ($c_canchas !== 200) { $errores[] = $r_canchas['error'] ?? 'No se pudieron cargar las canchas.'; }
if ($c_solicitudes_pass !== 200) { $errores[] = $r_solicitudes_pass['error'] ?? 'No se pudieron cargar las solicitudes de cambio de contraseña.'; }

// "Solicitudes pendientes": cuentas que necesitan que un administrador intervenga
// (deshabilitadas o bloqueadas por intentos fallidos de contraseña)
$solicitudes = array_values(array_filter($usuarios, function ($u) {
    return empty($u['is_active']) || usuario_bloqueado($u);
}));

$solicitudes_contrasena = ($c_solicitudes_pass === 200 && !empty($r_solicitudes_pass['success'])) ? $r_solicitudes_pass['data'] : [];

$titulos = [
    ''         => 'Panel de administrador',
    'canchas'  => 'Administrar canchas',
    'reservas' => 'Administrar reservas',
    'usuarios' => 'Administrar usuarios',
    'cambios'  => 'Cambios de contraseña',
];

$titulo = $titulos[$seccion];
$body_clase = "app";
$pagina_actual = $seccion === '' ? 'admin' : 'admin-' . $seccion;
include "partials/head.php";
?>
    <div class="app-shell shell-admin">
        <?php include "partials/sidebar.php"; ?>

        <main class="panel">
            <header class="panel-cabecera">
                <img class="logo-panel" src="images/logo.png" alt="Slotify">
                <h1 class="panel-titulo"><?= e($titulo) ?></h1>
            </header>

            <?php mostrar_flash(); ?>
            <?php foreach ($errores as $msg): ?>
                <p class="aviso aviso-error" role="alert"><?= e($msg) ?></p>
            <?php endforeach; ?>

            <?php if ($seccion === ''): ?>
                <!-- ============ Panel principal ============ -->
                <div class="barra-seccion">
                    <a class="boton-turquesa" href="admin-auditoria.php">Registros de auditoría</a>
                </div>
                <div class="admin-grid">
                    <section class="tarjeta-admin ancha">
                        <h2>Solicitudes pendientes</h2>
                        <ul class="lista-admin">
                            <?php foreach ($solicitudes as $u): ?>
                                <li>
                                    <span class="texto">
                                        <?= e($u['username']) ?> (<?= e($u['email']) ?>) -
                                        <?= empty($u['is_active']) ? 'validación de cuenta' : 'cuenta bloqueada por intentos fallidos' ?>
                                    </span>
                                    <?php acciones_fila('usuario', $u, ''); ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                        <?php if (empty($solicitudes)): ?><p class="vacio-chico">No hay solicitudes pendientes.</p><?php endif; ?>
                    </section>

                    <section class="tarjeta-admin ancha">
                        <h2><a href="admin.php?seccion=cambios">Cambios de contraseña solicitados</a></h2>
                        <ul class="lista-admin">
                            <?php foreach (array_slice($solicitudes_contrasena, 0, 3) as $s): ?>
                                <li>
                                    <span class="texto"><?= e($s['username']) ?> (<?= e($s['email'] ?? '') ?>) -
                                        pedida el <?= e(fecha_corta($s['requested_at'])) ?>
                                    </span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                        <?php if (empty($solicitudes_contrasena)): ?><p class="vacio-chico">No hay cambios de contraseña pendientes.</p><?php endif; ?>
                    </section>

                    <section class="tarjeta-admin ancha">
                        <h2><a href="admin.php?seccion=reservas">Últimas reservas</a></h2>
                        <ul class="lista-admin">
                            <?php foreach (array_slice($reservas, 0, 3) as $r): ?>
                                <li>
                                    <span class="texto">
                                        <?= e(fecha_corta($r['booking_date'])) ?> - <?= e(hora_corta($r['start_time'])) ?>,
                                        <?= e($r['court_name']) ?>, <?= e($r['username'] ?? '') ?>
                                        (<?= e(strtolower(estado_reserva_admin($r))) ?>)
                                    </span>
                                    <?php acciones_fila('reserva', $r, ''); ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                        <?php if (empty($reservas)): ?><p class="vacio-chico">Todavía no hay reservas.</p><?php endif; ?>
                    </section>

                    <section class="tarjeta-admin">
                        <h2><a href="admin.php?seccion=usuarios">Últimos usuarios creados</a></h2>
                        <ul class="lista-admin">
                            <?php foreach (array_slice($usuarios, 0, 5) as $u): ?>
                                <li>
                                    <span class="texto"><?= e($u['username']) ?></span>
                                    <?php acciones_fila('usuario', $u, ''); ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </section>

                    <section class="tarjeta-admin">
                        <h2><a href="admin.php?seccion=canchas">Últimas canchas añadidas</a></h2>
                        <ul class="lista-admin">
                            <?php foreach (array_slice($canchas, 0, 5) as $c): ?>
                                <li>
                                    <span class="texto"><?= e($c['name']) ?></span>
                                    <?php acciones_fila('cancha', $c, ''); ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </section>
                </div>

            <?php elseif ($seccion === 'canchas'): ?>
                <!-- ============ Administrar canchas ============ -->
                <div class="barra-seccion">
                    <a class="boton-turquesa" href="admin-cancha.php">Agregar cancha</a>
                </div>
                <div class="tabla-scroll">
                    <table class="tabla-admin">
                        <thead>
                            <tr><th>ID</th><th>Nombre</th><th>Tipo de cancha</th><th>Precio por hora</th><th>Estado</th><th>Acciones</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($canchas as $c): ?>
                                <tr>
                                    <td class="id"><?= e(id_formateado('C', $c['id'])) ?></td>
                                    <td><?= e($c['name']) ?></td>
                                    <td><?= e($c['court_type']) ?></td>
                                    <td><?= e(precio_formateado($c['price_per_hour'])) ?></td>
                                    <td><?= e(estado_cancha($c)) ?></td>
                                    <td class="celda-acciones"><?php acciones_fila('cancha', $c, 'canchas', true); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php if (empty($canchas)): ?><p class="vacio">Todavía no hay canchas cargadas.</p><?php endif; ?>

            <?php elseif ($seccion === 'reservas'): ?>
                <!-- ============ Administrar reservas ============ -->
                <div class="tabla-scroll">
                    <table class="tabla-admin">
                        <thead>
                            <tr><th>ID</th><th>Fecha</th><th>Usuario</th><th>Cancha</th><th>Estado</th><th>Acciones</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($reservas as $r): ?>
                                <tr>
                                    <td class="id"><?= e(id_formateado('R', $r['id'])) ?></td>
                                    <td><?= e(fecha_corta($r['booking_date'])) ?> - <?= e(hora_corta($r['start_time'])) ?></td>
                                    <td><?= e($r['username'] ?? '') ?></td>
                                    <td><?= e($r['court_name']) ?></td>
                                    <td><?= e(estado_reserva_admin($r)) ?></td>
                                    <td class="celda-acciones"><?php acciones_fila('reserva', $r, 'reservas', true); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php if (empty($reservas)): ?><p class="vacio">Todavía no hay reservas.</p><?php endif; ?>

            <?php elseif ($seccion === 'usuarios'): ?>
                <!-- ============ Administrar usuarios ============ -->
                <div class="barra-seccion">
                    <form method="GET" action="admin.php" role="search">
                        <input type="hidden" name="seccion" value="usuarios">
                        <input class="campo-app" type="search" name="email" value="<?= e($email_buscado) ?>"
                               placeholder="Buscar por correo electrónico" aria-label="Buscar usuarios por correo">
                        <button type="submit" class="boton-turquesa">Buscar</button>
                    </form>
                </div>
                <div class="tabla-scroll">
                    <table class="tabla-admin">
                        <thead>
                            <tr><th>ID</th><th>Usuario</th><th>Correo electrónico</th><th>Tipo de cuenta</th><th>Estado</th><th>Acciones</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($usuarios as $u): ?>
                                <tr>
                                    <td class="id"><?= e(id_formateado('U', $u['id'])) ?></td>
                                    <td><?= e($u['username']) ?></td>
                                    <td><?= e($u['email'] ?? '') ?></td>
                                    <td><?= ($u['role'] ?? '') === 'admin' ? 'Administrador' : 'Jugador' ?></td>
                                    <td><?= e(estado_usuario($u)) ?></td>
                                    <td class="celda-acciones"><?php acciones_fila('usuario', $u, 'usuarios', true); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php if (empty($usuarios)): ?><p class="vacio">No hay usuarios que coincidan.</p><?php endif; ?>

            <?php else: ?>
                <!-- ============ Cambios de contrasena ============ -->
                <?php if ($solicitudes_contrasena): ?>
                <div class="tabla-scroll">
                    <table class="tabla-admin">
                        <thead>
                            <tr><th>ID</th><th>Usuario</th><th>Correo electrónico</th><th>Fecha de solicitud</th><th>Nueva contraseña</th><th>Acciones</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($solicitudes_contrasena as $s): ?>
                                <tr>
                                    <td class="id"><?= e(id_formateado('P', $s['id'])) ?></td>
                                    <td><?= e($s['username']) ?></td>
                                    <td><?= e($s['email'] ?? '') ?></td>
                                    <td><?= e(fecha_corta($s['requested_at'])) ?></td>
                                    <td>
                                        <form class="form-inline-pass" method="POST" action="admin.php">
                                            <input type="hidden" name="accion" value="cambiar_password">
                                            <input type="hidden" name="id" value="<?= (int)$s['id'] ?>">
                                            <input type="hidden" name="seccion" value="cambios">
                                            <input class="campo-app campo-pass" type="password" name="password" required minlength="8"
                                                   placeholder="Nueva contraseña" autocomplete="new-password" aria-label="Nueva contraseña para <?= e($s['username']) ?>">
                                            <button type="submit" class="boton-turquesa">Cambiar</button>
                                        </form>
                                    </td>
                                    <td class="celda-acciones">
                                        <form class="ac" method="POST" action="admin.php"
                                              onsubmit="return confirm('¿Descartar la solicitud de <?= e($s['username']) ?> sin cambiar la contraseña?');">
                                            <input type="hidden" name="accion" value="descartar_solicitud">
                                            <input type="hidden" name="id" value="<?= (int)$s['id'] ?>">
                                            <input type="hidden" name="seccion" value="cambios">
                                            <button type="submit" title="Descartar solicitud" aria-label="Descartar solicitud">
                                                <img src="images/icon-eliminar.png" alt="">
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <p class="ayuda-campo">La contraseña debe tener entre 8 y 48 caracteres, con una mayúscula, un número y un símbolo. El usuario queda desbloqueado al cambiarle la clave.</p>
                <?php else: ?>
                    <p class="vacio">No hay solicitudes de cambio de contraseña pendientes. Las que hacen los usuarios desde "Recuperar contraseña" aparecen acá.</p>
                <?php endif; ?>
            <?php endif; ?>
        </main>
    </div>
<?php include "partials/foot.php"; ?>
