<?php
require "config.php";
exigir_login();

$usuario = $_SESSION['usuario'];

// Traemos la lista de canchas desde la API (ruta pública)
[$codigo, $res] = api_get('/api/courts');
$canchas = ($codigo === 200 && !empty($res['success'])) ? $res['data'] : [];
$error_canchas = ($codigo !== 200) ? ($res['error'] ?? 'No se pudieron cargar las canchas.') : '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Slotify - Inicio</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="contenedor-home">
        <div class="barra-lateral">
            <div class="saludo">
                Que gusto verte, <?= htmlspecialchars($usuario['username']) ?>
            </div>

            <?php if ($usuario['role'] === 'admin'): ?>
                <label style="display:flex; align-items:center; gap:8px; margin-bottom:16px;">
                    <input type="checkbox"> Panel de administrador
                </label>
            <?php endif; ?>

            <button class="opcion">🔍 Reservar Espacio</button>
            <button class="opcion">🗓️ Mis Reservas</button>
            <button class="opcion">👤 Ver Perfil</button>

            <a href="logout.php" class="opcion" style="margin-top:40px; background:#d9534f; text-align:center; justify-content:center;">
                ⏻ Cerrar Sesión
            </a>
        </div>

        <div class="contenido-home">
            <img src="images/logo.png" alt="Slotify" class="logo-img-chico" style="margin:0 0 14px;">
            <p>Elegí una opción del menú para comenzar</p>

            <?php if ($error_canchas): ?>
                <p class="error"><?= htmlspecialchars($error_canchas) ?></p>
            <?php elseif (empty($canchas)): ?>
                <p>Todavía no hay canchas cargadas.</p>
            <?php else: ?>
                <div class="lista-canchas">
                    <?php foreach ($canchas as $cancha): ?>
                        <div class="tarjeta-cancha">
                            <div class="foto-cancha">🎾</div>
                            <div class="info-cancha">
                                <p class="nombre-cancha"><?= htmlspecialchars($cancha['name']) ?></p>
                                <p><strong>Tipo de cancha:</strong> <?= htmlspecialchars($cancha['court_type']) ?></p>
                                <p><strong>Precio:</strong> $<?= number_format((float)$cancha['price_per_hour'], 0, ',', '.') ?> / hora</p>
                                <a href="disponibilidad.php?court_id=<?= urlencode($cancha['id']) ?>" class="boton-chico">
                                    Ver disponibilidad
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
