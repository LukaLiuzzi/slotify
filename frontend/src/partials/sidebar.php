<?php
/**
 * partials/sidebar.php
 * Menú lateral. Antes de incluirlo, la página define $pagina_actual:
 *   'home' | 'reservar' | 'reservas' | 'perfil' | 'admin' | (otra cosa)
 * En la pantalla donde estás, ese botón desaparece y arriba aparece "Volver".
 */
$usuario_actual = $_SESSION['usuario'];
$botones = [];

$es_admin_pagina = strpos($pagina_actual, 'admin') === 0;

if ($es_admin_pagina) {
    $secciones = [
        'admin-canchas'  => ['admin.php?seccion=canchas', 'icon-buscar.png', 'Administrar canchas'],
        'admin-reservas' => ['admin.php?seccion=reservas', 'icon-reservas.png', 'Administrar reservas'],
        'admin-usuarios' => ['admin.php?seccion=usuarios', 'icon-perfil.png', 'Administrar usuarios'],
        'admin-cambios'  => ['admin.php?seccion=cambios', 'icon-editar.png', 'Cambios de contraseña'],
        'admin-auditoria' => ['admin-auditoria.php', 'icon-detalle.png', 'Registros de auditoría'],
    ];
    if ($pagina_actual === 'admin') {
        $botones[] = ['home.php', 'icon-volver.png', 'Volver al panel de usuario', false];
    } else {
        $botones[] = ['admin.php', 'icon-volver.png', 'Volver al panel de administrador', false];
    }
    // La sección donde estás no aparece
    foreach ($secciones as $clave => $d) {
        if ($clave !== $pagina_actual) {
            $botones[] = [$d[0], $d[1], $d[2], false];
        }
    }
} else {
    if (es_admin()) {
        $botones[] = ['admin.php', 'icon-admin.png', 'Panel de administrador', true];
    }
    $destinos = [
        'reservar' => ['reservar.php', 'icon-buscar.png', 'Reservar Espacio'],
        'reservas' => ['mis-reservas.php', 'icon-reservas.png', 'Mis Reservas'],
        'perfil'   => ['perfil.php', 'icon-perfil.png', 'Ver Perfil'],
    ];
    // Fuera del home, "Volver" va primero y después los otros botones
    if (isset($destinos[$pagina_actual])) {
        $botones[] = ['home.php', 'icon-volver.png', 'Volver', false];
    }
    foreach ($destinos as $clave => $d) {
        if ($clave !== $pagina_actual) {
            $botones[] = [$d[0], $d[1], $d[2], false];
        }
    }
}
?>
<aside class="barra-lateral<?= $es_admin_pagina ? ' barra-admin' : '' ?>">
    <p class="saludo">Qué gusto verte, <?= e($usuario_actual['username']) ?></p>

    <nav class="menu" aria-label="Menú principal">
        <?php foreach ($botones as $b): ?>
            <a class="opcion<?= $b[3] ? ' opcion-morada' : '' ?>" href="<?= e($b[0]) ?>">
                <img src="images/<?= e($b[1]) ?>" alt="" width="46" height="46">
                <span><?= e($b[2]) ?></span>
            </a>
        <?php endforeach; ?>
    </nav>

    <a class="opcion opcion-roja" href="logout.php">
        <img src="images/icon-salir.png" alt="" width="46" height="46">
        <span>Cerrar Sesión</span>
    </a>
</aside>
