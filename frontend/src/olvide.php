<?php
require "config.php";

if (!empty($_SESSION['usuario'])) {
    redirigir("home.php");
}

$error = "";
$mensaje_ok = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $correo = trim($_POST['correo'] ?? '');

    if ($correo === '' || !filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        $error = "Ingresá un correo electrónico válido.";
    } else {
        // La API solo necesita el correo (el nombre de usuario es parte del diseño, pero no se envía)
        [$codigo, $res] = api_post('/api/auth/forgot-password', ['email' => $correo]);

        if ($codigo === 200 && !empty($res['success'])) {
            $mensaje_ok = $res['message'] ?? "Solicitud registrada. Un administrador te va a poner una nueva contraseña.";
        } else {
            $error = $res['error'] ?? "No se pudo procesar la solicitud.";
        }
    }
}

$titulo = "Recuperar contraseña";
$body_clase = "auth";
include "partials/head.php";
?>
    <a class="volver" href="index.php">Volver al inicio</a>
    <img class="logo-esquina" src="images/logo.png" alt="Slotify">

    <main class="auth-main">
        <h1 class="titulo-auth">Ingresa tus datos para<br>recuperar tu contraseña</h1>

        <form class="form-auth" method="POST" action="olvide.php">
            <label for="usuario">Nombre de Usuario</label>
            <input class="campo" type="text" id="usuario" name="usuario" required autocomplete="username"
                   value="<?= e($_POST['usuario'] ?? '') ?>">

            <label for="correo">Correo electrónico</label>
            <input class="campo" type="email" id="correo" name="correo" required autocomplete="email"
                   value="<?= e($_POST['correo'] ?? '') ?>">

            <?php if ($error): ?>
                <p class="aviso aviso-error" role="alert"><?= e($error) ?></p>
            <?php endif; ?>
            <?php if ($mensaje_ok): ?>
                <p class="aviso aviso-exito" role="status"><?= e($mensaje_ok) ?></p>
            <?php endif; ?>

            <button type="submit" class="boton-contorno">Recuperar contraseña</button>
        </form>
    </main>
<?php include "partials/foot.php"; ?>
