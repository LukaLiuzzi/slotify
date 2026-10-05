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
        // La API acepta "username" o "email" en el mismo campo de login.
        // Si lo que tipeó tiene un "@", lo mandamos como email.
        $clave_campo = (strpos($entrada, '@') !== false) ? 'email' : 'username';

        [$codigo, $res] = api_post('/api/auth/login', [
            $clave_campo => $entrada,
            'password'   => $contrasena,
        ]);

        if ($codigo === 200 && !empty($res['success'])) {
            // Guardamos los datos del usuario y el token CSRF en NUESTRA sesión
            $_SESSION['usuario'] = $res['data'];
            $_SESSION['csrf_token'] = $res['data']['csrf_token'] ?? '';
            redirigir("home.php");
        } elseif ($codigo === 429) {
            $error = $res['error'] ?? "Cuenta bloqueada temporalmente por intentos fallidos.";
        } elseif ($codigo === 0) {
            $error = $res['error']; // no se pudo conectar con la API
        } else {
            $error = $res['error'] ?? "Usuario o contraseña incorrectos.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Slotify - Iniciar sesión</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body style="position:relative;">
    <a href="index.php" class="volver">Volver al inicio</a>

    <div class="tarjeta">
        <img src="images/logo.png" alt="Slotify" class="logo-img logo-img-chico">
        <p class="lema">Ingresá tus datos para empezar</p>

        <form method="POST" action="login.php">
            <label class="etiqueta">Usuario o Correo</label>
            <input type="text" name="usuario" required
                   value="<?= htmlspecialchars($_POST['usuario'] ?? '') ?>">

            <label class="etiqueta">Contraseña</label>
            <input type="password" name="contrasena" required>

            <?php if ($error): ?>
                <p class="error"><?= htmlspecialchars($error) ?></p>
            <?php endif; ?>

            <button type="submit" class="boton">Iniciar Sesión</button>
        </form>
    </div>
</body>
</html>
