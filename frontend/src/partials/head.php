<?php
/**
 * partials/head.php
 * Cabecera común. Antes de incluirla, cada página define:
 *   $titulo      -> título de la pestaña
 *   $body_clase  -> "auth" (pantallas de acceso) o "app" (panel)
 */
$titulo = $titulo ?? 'Slotify';
$body_clase = $body_clase ?? 'app';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($titulo) ?> · Slotify</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Abril+Fatface&family=Inter:wght@400;500;600;700&family=Saira+Extra+Condensed:wght@700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <script>
        // Al tocar "Atrás", algunos navegadores muestran la página tal como estaba
        // (sin consultar al servidor). En ese caso la recargamos: el servidor
        // vuelve a revisar la sesión y, si ya cerraste sesión, te manda al login.
        window.addEventListener('pageshow', function (evento) {
            if (evento.persisted) { window.location.reload(); }
        });
    </script>
</head>
<body class="<?= e($body_clase) ?>">
