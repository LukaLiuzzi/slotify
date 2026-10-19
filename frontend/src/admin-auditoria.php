<?php
/**
 * admin-auditoria.php
 * Listado de registros de auditoría (audit_logs del backend) con filtros
 * por usuario, acción y resultado, y paginado chico.
 *
 * USA:  GET /api/admin/audit-logs?limit&offset&username&action&status
 */
require "config.php";
require_once "admin_helpers.php";
exigir_admin();

/* Acciones que puede registrar la API, con su rótulo en español. */
$acciones_posibles = [
    'LOGIN_SUCCESS'              => 'Inicio de sesión',
    'LOGIN_FAILED'               => 'Intento de acceso fallido',
    'LOGIN_BLOCKED'              => 'Cuenta bloqueada por intentos',
    'LOGIN_INACTIVE'             => 'Intento con cuenta deshabilitada',
    'LOGOUT'                     => 'Cierre de sesión',
    'REGISTER'                   => 'Registro de usuario',
    'FORGOT_PASSWORD_REQUEST'    => 'Pedido de cambio de contraseña',
    'PASSWORD_RESET_SUCCESS'     => 'Restablecimiento de contraseña',
    'ADMIN_CHANGE_PASSWORD'      => 'Contraseña cambiada (admin)',
    'CREATE_BOOKING'             => 'Reserva creada',
    'CREATE_BOOKING_FAILED'      => 'Reserva rechazada',
    'CANCEL_BOOKING'             => 'Reserva cancelada',
    'ADMIN_UPDATE_BOOKING_STATUS' => 'Estado de reserva actualizado (admin)',
    'ADMIN_CREATE_COURT'         => 'Cancha creada (admin)',
    'ADMIN_UPDATE_COURT'         => 'Cancha modificada (admin)',
    'ADMIN_DELETE_COURT'         => 'Cancha dada de baja (admin)',
    'ADMIN_UPDATE_USER_STATUS'   => 'Estado de usuario actualizado (admin)',
    'ADMIN_DELETE_USER'          => 'Usuario eliminado (admin)',
];

/* --- Filtros (por GET, para que se puedan compartir por link y volver) --- */
$f_usuario = trim($_GET['usuario'] ?? '');
$f_accion  = $_GET['accion'] ?? '';
if ($f_accion !== '' && !isset($acciones_posibles[$f_accion])) {
    $f_accion = '';
}
$f_estado = $_GET['estado'] ?? '';
if (!in_array($f_estado, ['success', 'failure'], true)) {
    $f_estado = '';
}

/* --- Paginado: la API no devuelve el total, así que pedimos una fila de más
       para saber si existe página siguiente --- */
$limite = 20;
$pagina = max(1, (int)($_GET['p'] ?? 1));
$offset = ($pagina - 1) * $limite;

$parametros = array_filter([
    'limit'    => $limite + 1,
    'offset'   => $offset,
    'username' => $f_usuario,
    'action'   => $f_accion,
    'status'   => $f_estado,
], function ($valor) { return $valor !== '' && $valor !== null; });

[$codigo, $res] = api_get('/api/admin/audit-logs?' . http_build_query($parametros));
cortar_si_sesion_vencida($codigo);

$logs = ($codigo === 200 && !empty($res['success'])) ? $res['data']['logs'] : [];
$error_carga = ($codigo !== 200 || empty($res['success']))
    ? ($res['error'] ?? 'No se pudieron cargar los registros de auditoría.')
    : '';

$hay_siguiente = count($logs) > $limite;
$logs = array_slice($logs, 0, $limite);

/** Link que conserva los filtros y cambia de página */
function link_pagina(int $p): string
{
    $params = array_filter([
        'usuario' => $_GET['usuario'] ?? '',
        'accion'  => $_GET['accion'] ?? '',
        'estado'  => $_GET['estado'] ?? '',
        'p'       => $p,
    ], function ($valor) { return $valor !== '' && $valor !== null; });
    return 'admin-auditoria.php?' . http_build_query($params);
}

$titulo = 'Registros de auditoría';
$body_clase = "app";
$pagina_actual = 'admin-auditoria';
include "partials/head.php";
?>
    <div class="app-shell shell-admin">
        <?php include "partials/sidebar.php"; ?>

        <main class="panel">
            <header class="panel-cabecera">
                <img class="logo-panel" src="images/logo.png" alt="Slotify">
                <h1 class="panel-titulo"><?= e($titulo) ?></h1>
            </header>

            <?php if ($error_carga !== ''): ?>
                <p class="aviso aviso-error" role="alert"><?= e($error_carga) ?></p>
            <?php endif; ?>

            <!-- ============ Filtros ============ -->
            <div class="barra-seccion">
                <form method="GET" action="admin-auditoria.php" role="search">
                    <input class="campo-app campo-filtro" type="search" name="usuario" value="<?= e($f_usuario) ?>"
                           placeholder="Usuario" aria-label="Filtrar por usuario">
                    <select class="campo-app campo-filtro" name="accion" aria-label="Filtrar por acción">
                        <option value="">Todas las acciones</option>
                        <?php foreach ($acciones_posibles as $clave => $rotulo): ?>
                            <option value="<?= e($clave) ?>"<?= $clave === $f_accion ? ' selected' : '' ?>>
                                <?= e($rotulo) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <select class="campo-app campo-filtro" name="estado" aria-label="Filtrar por resultado">
                        <option value="">Todos los resultados</option>
                        <option value="success"<?= $f_estado === 'success' ? ' selected' : '' ?>>Exitosos</option>
                        <option value="failure"<?= $f_estado === 'failure' ? ' selected' : '' ?>>Fallidos</option>
                    </select>
                    <button type="submit" class="boton-turquesa">Filtrar</button>
                    <a class="boton-peligro" href="admin-auditoria.php">Limpiar</a>
                </form>
            </div>

            <!-- ============ Listado ============ -->
            <div class="tabla-scroll">
                <table class="tabla-admin">
                    <thead>
                        <tr><th>ID</th><th>Fecha</th><th>Usuario</th><th>Acción</th><th>Resultado</th><th>IP</th><th>Detalle</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($logs as $log): ?>
                            <tr>
                                <td class="id"><?= (int)$log['id'] ?></td>
                                <td><?= e(date('d/m/Y H:i:s', strtotime($log['created_at']))) ?></td>
                                <td><?= e($log['username'] ?? '—') ?></td>
                                <td><?= e($acciones_posibles[$log['action']] ?? $log['action']) ?></td>
                                <td class="celda-resultado<?= ($log['status'] ?? '') === 'failure' ? ' resultado-fallido' : '' ?>">
                                    <?= ($log['status'] ?? '') === 'failure' ? 'Fallido' : 'Exitoso' ?>
                                </td>
                                <td><?= e($log['ip_address']) ?></td>
                                <td class="celda-detalle"><?= e($log['details'] ?? '') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php if (empty($logs) && $error_carga === ''): ?>
                <p class="vacio">No hay registros que coincidan con los filtros.</p>
            <?php endif; ?>

            <!-- ============ Paginación ============ -->
            <?php if ($pagina > 1 || $hay_siguiente): ?>
                <nav class="paginacion" aria-label="Paginación">
                    <?php if ($pagina > 1): ?>
                        <a class="boton-turquesa" href="<?= e(link_pagina($pagina - 1)) ?>">‹ Anterior</a>
                    <?php endif; ?>
                    <span class="paginacion-info">Página <?= $pagina ?></span>
                    <?php if ($hay_siguiente): ?>
                        <a class="boton-turquesa" href="<?= e(link_pagina($pagina + 1)) ?>">Siguiente ›</a>
                    <?php endif; ?>
                </nav>
            <?php endif; ?>
        </main>
    </div>
<?php include "partials/foot.php"; ?>
