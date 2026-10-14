<?php
require "config.php";

if (!empty($_SESSION['usuario'])) {
    redirigir("home.php");
}

$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario = trim($_POST['usuario'] ?? '');
    $correo = trim($_POST['correo'] ?? '');
    $contrasena = $_POST['contrasena'] ?? '';
    $confirmar = $_POST['confirmar'] ?? '';

    if ($usuario === '' || $correo === '' || $contrasena === '') {
        $error = "Completá todos los campos.";
    } elseif ($contrasena !== $confirmar) {
        // La API no pide confirmación: esta validación es solo del frontend
        $error = "Las contraseñas no coinciden.";
    } else {
        [$codigo, $res] = api_post('/api/auth/register', [
            'username' => $usuario,
            'email'    => $correo,
            'password' => $contrasena,
        ]);

        if ($codigo === 201 && !empty($res['success'])) {
            flash('exito', '¡Cuenta creada! Ya podés iniciar sesión.');
            redirigir("login.php");
        }

        // 400 = datos inválidos (la API explica cuál), 409 = usuario o correo ya existe
        $error = $res['error'] ?? "No se pudo completar el registro.";
    }
}

$titulo = "Registrarse";
$body_clase = "auth";
include "partials/head.php";
?>
    <a class="volver" href="index.php">Volver al inicio</a>
    <img class="logo-esquina" src="images/logo.png" alt="Slotify">

    <main class="auth-main">
        <h1 class="titulo-auth">Ingresa tus datos para registrarte</h1>

        <form class="form-auth form-compacto" method="POST" action="register.php">
            <label for="usuario">Nombre de Usuario</label>
            <input class="campo" type="text" id="usuario" name="usuario" required
                   pattern="[A-Za-z0-9_]{3,50}" title="Entre 3 y 50 caracteres: letras, números y guión bajo"
                   autocomplete="username" value="<?= e($_POST['usuario'] ?? '') ?>">

            <label for="correo">Correo Electrónico</label>
            <input class="campo" type="email" id="correo" name="correo" required autocomplete="email"
                   value="<?= e($_POST['correo'] ?? '') ?>">

            <label for="contrasena">Contraseña</label>
            <input class="campo" type="password" id="contrasena" name="contrasena" required minlength="8" autocomplete="new-password">
            <p class="ayuda-campo">Mínimo 8 caracteres, con una mayúscula, un número y un símbolo.</p>

            <label for="confirmar">Confirmar Contraseña</label>
            <input class="campo" type="password" id="confirmar" name="confirmar" required minlength="8" autocomplete="new-password">

            <?php if ($error): ?>
                <p class="aviso aviso-error" role="alert"><?= e($error) ?></p>
            <?php endif; ?>

            <button type="submit" class="boton-contorno">Registrate</button>
        </form>
    </main>
<?php include "partials/foot.php"; ?>
