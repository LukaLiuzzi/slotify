<?php
require "config.php";

// Si ya inició sesión, no tiene sentido mostrar la bienvenida
if (!empty($_SESSION['usuario'])) {
    redirigir("home.php");
}

$titulo = "Bienvenido";
$body_clase = "auth";
include "partials/head.php";
?>
    <main class="inicio">
        <img class="logo-inicio" src="images/logo.png" alt="Slotify">
        <p class="eslogan">Reservá tu espacio, enfocate en lo que importa</p>

        <p class="rotulo">Ingresá tu cuenta para comenzar</p>
        <a class="boton-blanco" href="login.php">Inicia Sesión</a>

        <p class="rotulo">¿No tenés cuenta?</p>
        <a class="boton-contorno" style="margin-top:0" href="register.php">Registrate</a>
    </main>
<?php include "partials/foot.php"; ?>
