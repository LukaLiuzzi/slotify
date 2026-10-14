<?php
require "config.php";

if (!empty($_SESSION['usuario'])) {
    redirigir("home.php");
}

$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $entrada = trim($_POST['usuario'] ?? '');
    $contrasena = $_POST['contrasena'] ?? '';

    if ($entrada === '' || $contrasena === '') {
        $error = "Completá usuario y contraseña.";
    } else {
        // La API acepta "username" o "email". Si tiene una @, lo mandamos como email.
        $clave_campo = (strpos($entrada, '@') !== false) ? 'email' : 'username';

        [$codigo, $res] = api_post('/api/auth/login', [
            $clave_campo => $entrada,
            'password'   => $contrasena,
        ]);

        if ($codigo === 200 && !empty($res['success'])) {
            // Nuevo ID de sesión al loguearse (evita "session fixation") y guardamos los datos
            session_regenerate_id(true);
            $_SESSION['usuario'] = $res['data'];
            $_SESSION['csrf_token'] = $res['data']['csrf_token'] ?? '';
            redirigir("home.php");
        }

        // 401 = credenciales mal, 429 = cuenta bloqueada, 403 = cuenta deshabilitada, 0 = sin conexión
        $error = $res['error'] ?? "Usuario o contraseña incorrectos.";
    }
}

$titulo = "Iniciar sesión";
$body_clase = "auth";
include "partials/head.php";
?>
    <a class="volver" href="index.php">Volver al inicio</a>
    <img class="logo-esquina" src="images/logo.png" alt="Slotify">

    <main class="auth-main">
        <h1 class="titulo-auth">Ingresa tus datos para empezar</h1>
        <?php mostrar_flash(); ?>

        <form class="form-auth" method="POST" action="login.php">
            <label for="usuario">Nombre de Usuario</label>
            <input class="campo" type="text" id="usuario" name="usuario" required autocomplete="username"
                   value="<?= e($_POST['usuario'] ?? '') ?>">

            <label for="contrasena">Contraseña</label>
            <input class="campo" type="password" id="contrasena" name="contrasena" required autocomplete="current-password">

            <?php if ($error): ?>
                <p class="aviso aviso-error" role="alert"><?= e($error) ?></p>
            <?php endif; ?>

            <p class="ayuda">¿Tenés problemas con tu contraseña?</p>
            <a class="boton-contorno chico" href="olvide.php">Reiniciar mi contraseña</a>

            <button type="submit" class="boton-contorno">Iniciar Sesión</button>
        </form>
    </main>
<?php include "partials/foot.php"; ?>
