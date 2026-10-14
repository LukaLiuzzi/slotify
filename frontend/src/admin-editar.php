<?php
require "config.php";
require_once "admin_helpers.php";
exigir_admin();

// Edición de usuarios (estado de la cuenta) y reservas (estado). Las canchas tienen su propia pantalla.
$tipo = $_GET['tipo'] ?? $_POST['tipo'] ?? '';
$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);

if ($tipo === 'cancha' && $id > 0) {
    redirigir('admin-cancha.php?id=' . $id);
}
if (!in_array($tipo, ['usuario', 'reserva'], true) || $id <= 0) {
    redirigir('admin.php');
}
$seccion = $tipo === 'usuario' ? 'usuarios' : 'reservas';
$ruta_lista = $tipo === 'usuario' ? '/api/admin/users' : '/api/admin/bookings';

[$codigo, $res] = api_get($ruta_lista);
cortar_si_sesion_vencida($codigo);
$fila = ($codigo === 200 && !empty($res['success'])) ? buscar_por_id($res['data'], $id) : null;
if ($fila === null) {
    flash('error', 'No se encontró el registro.');
    redirigir('admin.php?seccion=' . $seccion);
}

$error = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($tipo === 'usuario') {
        [$codigo, $res] = api_put("/api/admin/users/$id/status", [
            'is_active' => (int)($_POST['is_active'] ?? 1),
            'unlock'    => isset($_POST['unlock']),
        ]);
    } else {
        $estado = $_POST['status'] ?? '';
        if (!in_array($estado, ['confirmada', 'cancelada'], true)) {
            $estado = 'confirmada';
        }
        [$codigo, $res] = api_post("/api/admin/bookings/$id/status", ['status' => $estado]);
    }
    cortar_si_sesion_vencida($codigo);

    if ($codigo >= 200 && $codigo < 300 && !empty($res['success'])) {
        flash('exito', $res['message'] ?? 'Cambios guardados.');
        redirigir('admin.php?seccion=' . $seccion);
    }
    $error = $res['error'] ?? 'No se pudieron guardar los cambios.';
}

$titulo = $tipo === 'usuario' ? 'Editar usuario' : 'Editar reserva';
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

            <form class="formulario-panel" method="POST" action="admin-editar.php">
                <input type="hidden" name="tipo" value="<?= e($tipo) ?>">
                <input type="hidden" name="id" value="<?= e($id) ?>">

                <?php if ($tipo === 'usuario'): ?>
                    <p><strong><?= e($fila['username']) ?></strong> (<?= e($fila['email'] ?? '') ?>)</p>

                    <label for="is_active">Estado de la cuenta</label>
                    <select class="campo-app" id="is_active" name="is_active">
                        <option value="1" <?= !empty($fila['is_active']) ? 'selected' : '' ?>>Habilitada</option>
                        <option value="0" <?= empty($fila['is_active']) ? 'selected' : '' ?>>Deshabilitada</option>
                    </select>

                    <label class="check">
                        <input type="checkbox" name="unlock" value="1" <?= usuario_bloqueado($fila) ? 'checked' : '' ?>>
                        Desbloquear la cuenta (borra los intentos fallidos)
                    </label>
                <?php else: ?>
                    <p><strong><?= e($fila['court_name'] ?? '') ?></strong> · <?= e($fila['username'] ?? '') ?> ·
                       <?= e(fecha_corta($fila['booking_date'])) ?> <?= e(hora_corta($fila['start_time'])) ?></p>

                    <label for="status">Estado de la reserva</label>
                    <select class="campo-app" id="status" name="status">
                        <option value="confirmada" <?= ($fila['status'] ?? '') === 'confirmada' ? 'selected' : '' ?>>Reservado</option>
                        <option value="cancelada" <?= ($fila['status'] ?? '') === 'cancelada' ? 'selected' : '' ?>>Cancelado</option>
                    </select>
                <?php endif; ?>

                <?php if ($error): ?>
                    <p class="aviso aviso-error" role="alert"><?= e($error) ?></p>
                <?php endif; ?>

                <div class="botones">
                    <button type="submit" class="boton-turquesa">Guardar</button>
                    <a class="boton-turquesa boton-peligro" href="admin.php?seccion=<?= e($seccion) ?>">Cancelar</a>
                </div>
            </form>
        </main>
    </div>
<?php include "partials/foot.php"; ?>
