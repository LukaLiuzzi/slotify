<?php
require "config.php";

// Avisamos a la API que cierre la sesión (si no hay conexión, no pasa nada grave)
api_post('/api/auth/logout');

// Y borramos nuestra propia sesión de frontend
$_SESSION = [];
session_destroy();

redirigir("index.php");
