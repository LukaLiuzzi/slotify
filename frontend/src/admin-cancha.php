<?php
require "config.php";
exigir_admin();

// Con ?id=3 se edita la cancha 3; sin id se crea una nueva
$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
$editando = $id > 0;
$error = "";

$cancha = ['name' => '', 'court_type' => '', 'price_per_hour' => '', 'is_active' => 1];

if ($editando) {
    [$codigo, $res] = api_get("/api/courts/$id");
    if ($codigo === 200 && !empty($res['success'])) {
        $cancha = $res['data'];
    } else {
        flash('error', 'No se encontró la cancha.');
        redirigir('admin.php?seccion=canchas');
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $precio_texto = trim($_POST['price_per_hour'] ?? '');
    $datos = [
        'name'           => trim($_POST['name'] ?? ''),
        'court_type'     => trim($_POST['court_type'] ?? ''),
        'price_per_hour' => (float)str_replace(',', '.', $precio_texto),
        'is_active'      => isset($_POST['is_active']),
    ];

    // Si algo falla, el formulario vuelve a mostrar lo que se escribió
    $cancha = [
        'name'           => $datos['name'],
        'court_type'     => $datos['court_type'],
        'price_per_hour' => $precio_texto,
        'is_active'      => $datos['is_active'] ? 1 : 0,
    ];

    if ($datos['name'] === '' || $datos['court_type'] === '' || $datos['price_per_hour'] <= 0) {
        $error = "Completá el nombre, el tipo y un precio mayor a cero.";
    } else {
        [$codigo, $res] = $editando
            ? api_put("/api/admin/courts/$id", $datos)
            : api_post('/api/admin/courts', $datos);
        cortar_si_sesion_vencida($codigo);

        if ($codigo >= 200 && $codigo < 300 && !empty($res['success'])) {
            flash('exito', $res['message'] ?? 'Cancha guardada.');
            redirigir('admin.php?seccion=canchas');
        }
        $error = $res['error'] ?? "No se pudo guardar la cancha.";
    }
}

$titulo = $editando ? "Editar cancha" : "Agregar cancha";
$body_clase = "app";
$pagina_actual = "admin-canchas";
include "partials/head.php";
?>
    <div class="app-shell shell-admin">
        <?php include "partials/sidebar.php"; ?>

        <main class="panel">
            <header class="panel-cabecera">
                <img class="logo-panel" src="images/logo.png" alt="Slotify">
                <h1 class="panel-titulo"><?= e($titulo) ?></h1>
            </header>

            <form class="formulario-panel" method="POST" action="admin-cancha.php">
                <?php if ($editando): ?>
                    <input type="hidden" name="id" value="<?= e($id) ?>">
                <?php endif; ?>

                <label for="name">Nombre</label>
                <input class="campo-app" type="text" id="name" name="name" required value="<?= e($cancha['name']) ?>">

                <label for="court_type">Tipo de cancha</label>
                <input class="campo-app" type="text" id="court_type" name="court_type" required
                       placeholder="Ej: Cristal Panorámica Techada" value="<?= e($cancha['court_type']) ?>">

                <label for="price_per_hour">Precio por hora ($)</label>
                <input class="campo-app" type="number" id="price_per_hour" name="price_per_hour" required
                       min="1" step="0.01" value="<?= e($cancha['price_per_hour']) ?>">

                <label class="check">
                    <input type="checkbox" name="is_active" value="1" <?= !empty($cancha['is_active']) ? 'checked' : '' ?>>
                    Cancha activa (se puede reservar)
                </label>

                <?php if ($error): ?>
                    <p class="aviso aviso-error" role="alert"><?= e($error) ?></p>
                <?php endif; ?>

                <div class="botones">
                    <button type="submit" class="boton-turquesa">Guardar</button>
                    <a class="boton-turquesa boton-peligro" href="admin.php?seccion=canchas">Cancelar</a>
                </div>
            </form>
        </main>
    </div>
<?php include "partials/foot.php"; ?>
