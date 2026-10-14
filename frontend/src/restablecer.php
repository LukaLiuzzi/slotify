<?php
require "config.php";

if (!empty($_SESSION['usuario'])) {
    redirigir("home.php");
}

$error = "";
$token = trim($_POST['token'] ?? $_GET['token'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $contrasena = $_POST['contrasena'] ?? '';
    $confirmar = $_POST['confirmar'] ?? '';

    if ($token === '' || $contrasena === '') {
        $error = "Completá el código y la nueva contraseña.";
    } elseif ($contrasena !== $confirmar) {
        $error = "Las contraseñas no coinciden.";
    } else {
        [$codigo, $res] = api_post('/api/auth/reset-password', [
            'token'    => $token,
            'password' => $contrasena,
        ]);

        if ($codigo === 200 && !empty($res['success'])) {
            flash('exito', $res['message'] ?? 'Contraseña actualizada. Ya podés iniciar sesión.');
            redirigir("login.php");
        }
        $error = $res['error'] ?? "No se pudo restablecer la contraseña.";
    }
}

$titulo = "Nueva contraseña";
$body_clase = "auth";
include "partials/head.php";
?>
    <a class="volver" href="index.php">Volver al inicio</a>
    <img class="logo-esquina" src="images/logo.png" alt="Slotify">

    <main class="auth-main">
        <h1 class="titulo-auth">Elegí tu nueva contraseña</h1>

        <form class="form-auth form-compacto" method="POST" action="restablecer.php">
            <label for="token">Código de recuperación</label>
            <input class="campo" type="text" id="token" name="token" required autocomplete="off"
                   value="<?= e($token) ?>">

            <label for="contrasena">Nueva contraseña</label>
            <input class="campo" type="password" id="contrasena" name="contrasena" required minlength="8" autocomplete="new-password">
            <p class="ayuda-campo">Mínimo 8 caracteres, con una mayúscula, un número y un símbolo.</p>

            <label for="confirmar">Confirmar contraseña</label>
            <input class="campo" type="password" id="confirmar" name="confirmar" required minlength="8" autocomplete="new-password">

            <?php if ($error): ?>
                <p class="aviso aviso-error" role="alert"><?= e($error) ?></p>
            <?php endif; ?>

            <button type="submit" class="boton-contorno">Guardar contraseña</button>
        </form>
    </main>
<?php include "partials/foot.php"; ?>
