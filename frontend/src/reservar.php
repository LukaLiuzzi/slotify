<?php
require "config.php";
exigir_login();

$q = trim($_GET['q'] ?? '');
$orden_precio = (($_GET['orden'] ?? '') === 'precio');

[$codigo, $res] = api_get('/api/courts');
$todas = ($codigo === 200 && !empty($res['success'])) ? $res['data'] : [];
$error_canchas = ($codigo !== 200) ? ($res['error'] ?? 'No se pudieron cargar las canchas.') : '';

// El buscador filtra por nombre o por tipo de cancha
$canchas = array_values(array_filter($todas, function ($c) use ($q) {
    return contiene($c['name'] . ' ' . $c['court_type'], $q);
}));

if ($orden_precio) {
    usort($canchas, function ($a, $b) {
        return (float)$a['price_per_hour'] <=> (float)$b['price_per_hour'];
    });
}

// El ícono de filtros alterna el orden por precio (conservando la búsqueda)
$params_orden = [];
if ($q !== '') {
    $params_orden['q'] = $q;
}
if (!$orden_precio) {
    $params_orden['orden'] = 'precio';
}
$link_orden = 'reservar.php' . ($params_orden ? '?' . http_build_query($params_orden) : '');

$titulo = "Reservar espacio";
$body_clase = "app";
$pagina_actual = "reservar";
include "partials/head.php";
?>
    <div class="app-shell">
        <?php include "partials/sidebar.php"; ?>

        <main class="panel">
            <header class="panel-cabecera">
                <img class="logo-panel" src="images/logo.png" alt="Slotify">
                <h1 class="panel-titulo">Buscar reservas</h1>
            </header>

            <?php mostrar_flash(); ?>

            <form class="buscador" method="GET" action="reservar.php" role="search">
                <a class="buscador-filtros" href="<?= e($link_orden) ?>"
                   title="<?= $orden_precio ? 'Quitar orden por precio' : 'Ordenar por precio (menor a mayor)' ?>">
                    <img src="images/icon-filtros.png" alt="Ordenar por precio">
                </a>
                <input type="search" name="q" value="<?= e($q) ?>" placeholder="Buscar por nombre o tipo de cancha"
                       aria-label="Buscar canchas">
                <?php if ($orden_precio): ?>
                    <input type="hidden" name="orden" value="precio">
                <?php endif; ?>
                <button type="submit" aria-label="Buscar"><img src="images/icon-lupa.png" alt=""></button>
            </form>

            <?php if ($error_canchas): ?>
                <p class="aviso aviso-error" role="alert"><?= e($error_canchas) ?></p>
            <?php else: ?>
                <section class="resultados">
                    <h2 class="resultados-titulo">
                        <?= $q !== '' ? 'Resultados para “' . e($q) . '”' : 'Resultados: Todas las canchas' ?>
                        <?= $orden_precio ? ' · por precio' : '' ?>
                    </h2>

                    <?php if (empty($canchas)): ?>
                        <p class="vacio">No encontramos canchas con ese nombre o tipo.</p>
                    <?php endif; ?>

                    <?php foreach ($canchas as $cancha): ?>
                        <article class="fila-cancha">
                            <img src="<?= e(foto_cancha($cancha['id'])) ?>" alt="Foto de <?= e($cancha['name']) ?>">
                            <div class="datos-cancha">
                                <p class="nombre"><?= e($cancha['name']) ?></p>
                                <p><strong>Tipo de cancha:</strong> <?= e($cancha['court_type']) ?></p>
                                <p><strong>Precio:</strong> <?= e(precio_formateado($cancha['price_per_hour'])) ?> / hora</p>
                            </div>
                            <a class="boton-turquesa" href="disponibilidad.php?court_id=<?= urlencode($cancha['id']) ?>">Ver disponibilidad</a>
                        </article>
                    <?php endforeach; ?>
                </section>
            <?php endif; ?>
        </main>
    </div>
<?php include "partials/foot.php"; ?>
