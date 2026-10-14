<?php
require "config.php";
exigir_login();

$usuario = $_SESSION['usuario'];

// Lista de canchas (ruta pública de la API)
[$codigo, $res] = api_get('/api/courts');
$canchas = ($codigo === 200 && !empty($res['success'])) ? $res['data'] : [];
$error_canchas = ($codigo !== 200) ? ($res['error'] ?? 'No se pudieron cargar las canchas.') : '';

$titulo = "Inicio";
$body_clase = "app";
$pagina_actual = "home";
include "partials/head.php";
?>
    <div class="app-shell">
        <?php include "partials/sidebar.php"; ?>

        <main class="panel">
            <header class="panel-cabecera">
                <img class="logo-panel" src="images/logo.png" alt="Slotify">
                <h1 class="panel-titulo"><?= e($usuario['username']) ?>, te traemos opciones que pueden interesarte</h1>
            </header>

            <?php mostrar_flash(); ?>

            <?php if ($error_canchas): ?>
                <p class="aviso aviso-error" role="alert"><?= e($error_canchas) ?></p>
            <?php elseif (empty($canchas)): ?>
                <p class="vacio">Todavía no hay canchas cargadas.</p>
            <?php else: ?>
                <div class="lista-canchas">
                    <?php foreach ($canchas as $cancha): ?>
                        <article class="tarjeta-cancha">
                            <div class="foto">
                                <img src="<?= e(foto_cancha($cancha['id'])) ?>" alt="Foto de <?= e($cancha['name']) ?>">
                                <a class="boton-turquesa" href="disponibilidad.php?court_id=<?= urlencode($cancha['id']) ?>">Ver disponibilidad</a>
                            </div>
                            <div class="datos-cancha">
                                <p class="nombre"><?= e($cancha['name']) ?></p>
                                <p><strong>Tipo de cancha:</strong> <?= e($cancha['court_type']) ?></p>
                                <p><strong>Precio:</strong> <?= e(precio_formateado($cancha['price_per_hour'])) ?> / hora</p>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </main>
    </div>
<?php include "partials/foot.php"; ?>
