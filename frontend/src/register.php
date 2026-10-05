<?php
require "config.php";

if (!empty($_SESSION['usuario'])) {
    redirigir("home.php");
}

$error = "";
$exito = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario = trim($_POST['usuario'] ?? '');
    $correo = trim($_POST['correo'] ?? '');
    $contrasena = $_POST['contrasena'] ?? '';
    $confirmar = $_POST['confirmar'] ?? '';

    if ($usuario === '' || $correo === '' || $contrasena === '') {
        $error = "Completá todos los campos.";
    } elseif ($contrasena !== $confirmar) {
        // La API no pide confirmación, así que esta validación es solo del frontend
        $error = "Las contraseñas no coinciden.";
    } else {
        [$codigo, $res] = api_post('/api/auth/register', [
            'username' => $usuario,
            'email'    => $correo,
            'password' => $contrasena,
        ]);

        if ($codigo === 201 && !empty($res['success'])) {
            $exito = "¡Cuenta creada con éxito! Ya podés iniciar sesión.";
        } elseif ($codigo === 409) {
            $error = $res['error'] ?? "Ese usuario o correo ya está registrado.";
        } elseif ($codigo === 0) {
            $error = $res['error'];
        } else {
            // La API devuelve en "error" el motivo exacto (ej: reglas de la contraseña)
            $error = $res['error'] ?? "No se pudo completar el registro.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Slotify - Registrarse</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body style="position:relative;">
    <a href="index.php" class="volver">Volver al inicio</a>

    <div class="tarjeta">
        <img src="images/logo.png" alt="Slotify" class="logo-img logo-img-chico">
        <p class="lema">Ingresá tus datos para registrarte</p>

        <form method="POST" action="register.php">
            <label class="etiqueta">Nombre de Usuario</label>
            <input type="text" name="usuario" required
                   value="<?= htmlspecialchars($_POST['usuario'] ?? '') ?>">

            <label class="etiqueta">Correo Electrónico</label>
            <input type="email" name="correo" required
                   value="<?= htmlspecialchars($_POST['correo'] ?? '') ?>">

            <label class="etiqueta">Contraseña</label>
            <input type="password" name="contrasena" required>
            <small style="color:#0d3b52; display:block; margin-top:4px;">
                Mínimo 8 caracteres, con una mayúscula, un número y un símbolo.
            </small>

            <label class="etiqueta">Confirmar Contraseña</label>
            <input type="password" name="confirmar" required>

            <?php if ($error): ?>
                <p class="error"><?= htmlspecialchars($error) ?></p>
            <?php endif; ?>
            <?php if ($exito): ?>
                <p class="exito"><?= htmlspecialchars($exito) ?>
                    <a href="login.php">Iniciar sesión</a></p>
            <?php endif; ?>

            <button type="submit" class="boton">Registrate</button>
        </form>
    </div>
</body>
</html>
