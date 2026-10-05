<?php
require "config.php";

// Si el usuario ya inició sesión, lo mandamos directo al home
if (isset($_SESSION['usuario_id'])) {
    redirigir("home.php");
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Slotify</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="tarjeta">
        <img src="images/logo.png" alt="Slotify" class="logo-img">
        <p class="lema">Reservá tu espacio, enfocate en lo que importa</p>

        <p class="etiqueta" style="text-align:center;">Ingresá a tu cuenta para comenzar</p>
        <a href="login.php" class="boton">Inicia Sesión</a>

        <p class="etiqueta" style="text-align:center; margin-top:28px;">¿No tenés cuenta?</p>
        <a href="register.php" class="boton boton-borde">Registrate</a>
    </div>
</body>
</html>
