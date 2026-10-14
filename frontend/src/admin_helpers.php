<?php
/**
 * admin_helpers.php
 * -----------------
 * Piezas que comparten las pantallas de administración.
 * Se incluye con:  require_once "admin_helpers.php";
 */

/** 7 -> "C0007" (prefijo C = cancha, R = reserva, U = usuario) */
function id_formateado(string $prefijo, $id): string
{
    return $prefijo . str_pad((string)(int)$id, 4, '0', STR_PAD_LEFT);
}

function usuario_bloqueado(array $u): bool
{
    return !empty($u['locked_until']) && strtotime($u['locked_until']) > time();
}

function estado_usuario(array $u): string
{
    if (empty($u['is_active'])) {
        return 'Deshabilitado';
    }
    return usuario_bloqueado($u) ? 'Bloqueado' : 'Activo';
}

function estado_cancha(array $c): string
{
    return !empty($c['is_active']) ? 'Habilitada' : 'Inactiva';
}

/** Reservado / Finalizado / Cancelado (usa estado_reserva() de helpers.php) */
function estado_reserva_admin(array $r): string
{
    [$texto] = estado_reserva($r);
    $mapa = ['Confirmada' => 'Reservado', 'Finalizada' => 'Finalizado', 'Cancelada' => 'Cancelado'];
    return $mapa[$texto] ?? $texto;
}

/** Busca dentro de una lista el elemento con ese id (o null). */
function buscar_por_id(array $lista, int $id): ?array
{
    foreach ($lista as $fila) {
        if ((int)$fila['id'] === $id) {
            return $fila;
        }
    }
    return null;
}

/**
 * Los tres botones de cada fila: ver · editar · borrar.
 *
 * @param string $tipo     'cancha' | 'reserva' | 'usuario'
 * @param array  $fila     datos de esa fila, tal como los devuelve la API
 * @param string $seccion  a qué listado volver después de una acción ('' = panel principal)
 * @param bool   $rotulos  true = muestra "ver / editar / borrar" debajo de los íconos
 */
function acciones_fila(string $tipo, array $fila, string $seccion, bool $rotulos = false): void
{
    $id = (int)$fila['id'];
    $link_ver = 'admin-ver.php?tipo=' . $tipo . '&id=' . $id;
    $link_editar = ($tipo === 'cancha')
        ? 'admin-cancha.php?id=' . $id
        : 'admin-editar.php?tipo=' . $tipo . '&id=' . $id;

    // "Borrar" hace algo distinto según el caso (y no siempre está permitido)
    if ($tipo === 'cancha') {
        $accion = 'eliminar_cancha';
        $permitido = !empty($fila['is_active']);
        $titulo = 'Dar de baja la cancha';
        $confirmacion = '¿Dar de baja la cancha ' . $fila['name'] . '?';
        $rotulo = 'borrar';
    } elseif ($tipo === 'usuario') {
        $accion = 'eliminar_usuario';
        $permitido = ($fila['role'] ?? '') !== 'admin';
        $titulo = 'Eliminar usuario';
        $confirmacion = '¿Eliminar al usuario ' . $fila['username'] . '? No se puede deshacer.';
        $rotulo = 'borrar';
    } else {
        $accion = 'cancelar_reserva';
        $permitido = reserva_cancelable($fila);
        $titulo = 'Cancelar reserva';
        $confirmacion = '¿Cancelar esta reserva?';
        $rotulo = 'cancelar';
    }
    ?>
    <div class="acciones-fila">
        <a class="ac" href="<?= e($link_ver) ?>" title="Ver detalle" aria-label="Ver detalle">
            <img src="images/icon-detalle.png" alt="">
        </a>
        <a class="ac" href="<?= e($link_editar) ?>" title="Editar" aria-label="Editar">
            <img src="images/icon-editar.png" alt="">
        </a>
        <?php if ($permitido): ?>
            <form class="ac" method="POST" action="admin.php"
                  onsubmit="return confirm(<?= e(json_encode($confirmacion)) ?>);">
                <input type="hidden" name="accion" value="<?= e($accion) ?>">
                <input type="hidden" name="id" value="<?= e($id) ?>">
                <input type="hidden" name="seccion" value="<?= e($seccion) ?>">
                <button type="submit" title="<?= e($titulo) ?>" aria-label="<?= e($titulo) ?>">
                    <img src="images/icon-eliminar.png" alt="">
                </button>
            </form>
        <?php else: ?>
            <span class="ac ac-inactivo" title="No disponible para este registro">
                <img src="images/icon-eliminar.png" alt="">
            </span>
        <?php endif; ?>
        <?php if ($rotulos): ?>
            <span class="rotulo">ver</span>
            <span class="rotulo">editar</span>
            <span class="rotulo"><?= e($rotulo) ?></span>
        <?php endif; ?>
    </div>
    <?php
}
