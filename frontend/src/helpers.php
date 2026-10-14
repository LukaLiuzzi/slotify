<?php
/**
 * helpers.php
 * -----------
 * Funciones chicas que se reutilizan en varias pantallas.
 * Se carga solo, desde config.php.
 */

// Todas las fechas y horas se calculan en hora de Argentina
date_default_timezone_set('America/Argentina/Buenos_Aires');

/** Escapa texto antes de mostrarlo en HTML (evita XSS). Usala siempre que muestres un dato: e($dato) */
function e($valor): string
{
    return htmlspecialchars((string)$valor, ENT_QUOTES, 'UTF-8');
}

/** 15000 -> "$15.000" */
function precio_formateado($precio): string
{
    return '$' . number_format((float)$precio, 0, ',', '.');
}

/** "2026-09-27" -> "27/09/2026" */
function fecha_corta(string $fecha): string
{
    $ts = strtotime($fecha);
    return $ts ? date('d/m/Y', $ts) : $fecha;
}

/** "19:30:00" -> "19:30" */
function hora_corta(string $hora): string
{
    return substr($hora, 0, 5);
}

/**
 * La API no devuelve foto de la cancha, así que se asigna una de las 3 fotos
 * que hay en images/ según el id (la 4ta cancha vuelve a usar la primera, etc.).
 * Cuando el backend sume una columna "image", se cambia solo esta función.
 */
function foto_cancha($id): string
{
    $fotos = ['cancha-panoramica.jpg', 'cancha-cesped.jpg', 'cancha-exterior.jpg'];
    $n = count($fotos);
    $i = (((int)$id - 1) % $n + $n) % $n;
    return 'images/' . $fotos[$i];
}

/** ¿Hay un texto dentro de otro? Sin importar mayúsculas/minúsculas. */
function contiene(string $texto, string $buscado): bool
{
    if ($buscado === '') {
        return true;
    }
    if (function_exists('mb_stripos')) {
        return mb_stripos($texto, $buscado, 0, 'UTF-8') !== false;
    }
    return stripos($texto, $buscado) !== false;
}

function es_admin(): bool
{
    return ($_SESSION['usuario']['role'] ?? '') === 'admin';
}

/** Solo deja pasar a administradores; al resto lo manda al home. */
function exigir_admin(): void
{
    exigir_login();
    if (!es_admin()) {
        redirigir('home.php');
    }
}

/* ---------- Avisos que sobreviven a una redirección ---------- */

function flash(string $tipo, string $mensaje): void
{
    $_SESSION['flash'] = ['tipo' => $tipo, 'mensaje' => $mensaje];
}

/** Dibuja el aviso pendiente (si hay) y lo borra para que no se repita. */
function mostrar_flash(): void
{
    $f = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    if ($f) {
        echo '<p class="aviso aviso-' . e($f['tipo']) . '" role="status">' . e($f['mensaje']) . '</p>';
    }
}

/**
 * Si la API contesta 401 es que la sesión del backend venció:
 * limpiamos la nuestra y mandamos a iniciar sesión de nuevo.
 */
function cortar_si_sesion_vencida(int $codigo): void
{
    if ($codigo === 401) {
        $_SESSION = [];
        flash('error', 'Tu sesión expiró. Iniciá sesión de nuevo.');
        redirigir('login.php');
    }
}

/* ---------- Estado de una reserva ---------- */

/** Devuelve [texto, clase CSS]. Una confirmada con fecha pasada se muestra "Finalizada". */
function estado_reserva(array $r): array
{
    if (($r['status'] ?? '') === 'cancelada') {
        return ['Cancelada', 'estado-cancelada'];
    }
    $fin = strtotime(($r['booking_date'] ?? '') . ' ' . ($r['end_time'] ?? '23:59:59'));
    if ($fin && $fin < time()) {
        return ['Finalizada', 'estado-finalizada'];
    }
    return ['Confirmada', 'estado-confirmada'];
}

/** ¿Se puede cancelar? Solo si está confirmada y todavía no empezó. */
function reserva_cancelable(array $r): bool
{
    if (($r['status'] ?? '') !== 'confirmada') {
        return false;
    }
    $inicio = strtotime(($r['booking_date'] ?? '') . ' ' . ($r['start_time'] ?? '00:00:00'));
    return $inicio && $inicio > time();
}
