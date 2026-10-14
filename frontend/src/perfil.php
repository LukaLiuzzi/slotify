<?php
require "config.php";
exigir_login();

// Pedimos los datos frescos a la API (por si cambiaron desde el login)
[$codigo, $res] = api_get('/api/auth/me');
cortar_si_sesion_vencida($codigo);

$yo = ($codigo === 200 && !empty($res['success'])) ? $res['data'] : $_SESSION['usuario'];

$titulo = "Mi perfil";
$body_clase = "app";
$pagina_actual = "perfil";
include "partials/head.php";
?>
    <div class="app-shell">
        <?php include "partials/sidebar.php"; ?>

        <main class="panel">
            <header class="panel-cabecera">
                <img class="logo-panel" src="images/logo.png" alt="Slotify">
                <h1 class="panel-titulo">Bienvenido, <?= e($yo['username']) ?></h1>
            </header>

            <?php mostrar_flash(); ?>

            <section class="perfil-cabecera">
                <div>
                    <p class="perfil-nombre"><?= e($yo['username']) ?></p>
                    <img class="avatar" src="images/icon-perfil.png" alt="Avatar de <?= e($yo['username']) ?>">
                </div>

                <!-- Todavía no hay endpoints en la API para estas tres acciones -->
                <div class="acciones-perfil">
                    <button type="button" class="boton-turquesa" disabled title="Próximamente">Cambiar foto de perfil</button>
                    <button type="button" class="boton-turquesa" disabled title="Próximamente">Editar información</button>
                    <button type="button" class="boton-turquesa" disabled title="Próximamente">Cambiar contraseña</button>
                </div>
            </section>

            <section class="datos-usuario">
                <h2>Información del usuario</h2>
                <dl>
                    <dt>Nombre de usuario:</dt>
                    <dd><?= e($yo['username']) ?></dd>

                    <dt>Correo Electrónico:</dt>
                    <dd><?= e($yo['email']) ?></dd>

                    <dt>Tipo de cuenta:</dt>
                    <dd><?= ($yo['role'] ?? '') === 'admin' ? 'Administrador' : 'Jugador' ?></dd>

                    <?php if (isset($yo['is_active'])): ?>
                        <dt>Estado:</dt>
                        <dd><?= !empty($yo['is_active']) ? 'Cuenta activa' : 'Cuenta deshabilitada' ?></dd>
                    <?php endif; ?>
                </dl>
            </section>
        </main>
    </div>
<?php include "partials/foot.php"; ?>
