INSERT INTO roles (id, name) VALUES 
(1, 'admin'),
(2, 'usuario')
ON DUPLICATE KEY UPDATE name=VALUES(name);

INSERT INTO users (id, username, email, password_hash, role_id, is_active) VALUES 
(1, 'admin', 'admin@slotify.com', '$2y$12$J0QEAbpnBr4dlbiqpgMTfuowqcDAa7wvET6Xe9OKd2V1hb/31T752', 1, 1),
(2, 'jugador1', 'jugador1@slotify.com', '$2y$12$nW0xNiJow4IfCg3q6qO6a.Fgv2m35iw5RNWQNV4DNhVE6T9K6914a', 2, 1)
ON DUPLICATE KEY UPDATE username=VALUES(username);

INSERT INTO courts (id, name, court_type, price_per_hour, is_active) VALUES 
(1, 'Cancha Central', 'Cristal Panorámica Techada', 15000.00, 1),
(2, 'Cancha 2', 'Cristal Césped Sintético', 12000.00, 1),
(3, 'Cancha 3', 'Muro Tradicional Exterior', 10000.00, 1)
ON DUPLICATE KEY UPDATE name=VALUES(name);

INSERT INTO bookings (id, court_id, user_id, booking_date, start_time, end_time, status) VALUES
(1, 1, 2, '2026-09-10', '18:00:00', '19:00:00', 'confirmada')
ON DUPLICATE KEY UPDATE status=VALUES(status);

INSERT INTO audit_logs (user_id, username, action, ip_address, user_agent, status, details) VALUES
(1, 'admin', 'SYSTEM_INITIALIZATION', '127.0.0.1', 'SeedScript', 'success', 'Base de datos inicializada correctamente');
