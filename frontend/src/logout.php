<?php
require "config.php";

// Avisamos a la API que cierre la sesión (si no hay conexión, no pasa nada grave)
api_post('/api/auth/logout');

// Borramos nuestra propia sesión de frontend: datos, cookie y sesión en el servidor
$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $c = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $c['path'], $c['domain'], $c['secure'], $c['httponly']);
}
session_destroy();

redirigir("index.php");
