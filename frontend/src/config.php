<?php
/**
 * config.php
 * -----------
 * Ya no hay conexión a MySQL acá: el "backend" ahora es tu API REST
 * (http://localhost:8080). Este archivo define:
 *   1) session_start() - nuestra propia sesión de PHP (la del frontend)
 *   2) api_call()       - función genérica para hablar con la API por cURL
 *   3) api_get/api_post/api_put/api_delete - atajos sobre api_call()
 *
 * IMPORTANTE: nuestra sesión de PHP (la del frontend) es DISTINTA de la
 * sesión que crea la API (PHPSESSID de http://localhost:8080). Como las
 * peticiones a la API las hace el servidor (con cURL) y no el navegador
 * del usuario, tenemos que guardar nosotros mismos la cookie que nos
 * devuelve la API la primera vez (login) y reenviarla a mano en cada
 * pedido siguiente. Lo mismo con el token CSRF.
 */

// Nombre propio para la cookie de sesión del frontend. Así no se pisa con la
// PHPSESSID del backend (los navegadores comparten cookies entre puertos
// distintos del mismo "localhost").
session_name('SLOTIFY_FRONT');
session_start();

// --- Dirección base de tu API ---
// Con Docker viene de la variable de entorno API_BASE_URL (ver docker-compose.yml).
// Si no existe (ej: corriendo con XAMPP), usa http://localhost:8080.
define('API_BASE_URL', getenv('API_BASE_URL') ?: 'http://localhost:8080');

/**
 * Hace una petición HTTP a la API y devuelve [codigo_http, datos_decodificados].
 *
 * @param string $method  GET | POST | PUT | DELETE
 * @param string $path    ej: "/api/auth/login"
 * @param array|null $body  cuerpo a enviar como JSON (o null si no hay)
 */
function api_call(string $method, string $path, ?array $body = null): array
{
    $ch = curl_init(API_BASE_URL . $path);

    $headers = ["Content-Type: application/json"];

    // Reenviamos la cookie de sesión de la API, si ya la tenemos guardada
    if (!empty($_SESSION['api_cookie'])) {
        $headers[] = "Cookie: " . $_SESSION['api_cookie'];
    }

    // Las rutas que escriben datos (POST/PUT/DELETE) exigen el token CSRF
    if (in_array($method, ['POST', 'PUT', 'DELETE']) && !empty($_SESSION['csrf_token'])) {
        $headers[] = "X-CSRF-Token: " . $_SESSION['csrf_token'];
    }

    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, true); // necesitamos ver los headers (Set-Cookie)
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);

    if ($body !== null) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
    }

    $respuesta = curl_exec($ch);

    if ($respuesta === false) {
        $motivo = curl_error($ch);
        curl_close($ch);
        // Código 0 = no hubo respuesta (API caída, Docker no está levantado, etc.)
        return [0, ['success' => false, 'error' => "No se pudo conectar con la API: $motivo"]];
    }

    $tamano_headers = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $codigo_http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $texto_headers = substr($respuesta, 0, $tamano_headers);
    $texto_cuerpo = substr($respuesta, $tamano_headers);
    curl_close($ch);

    guardar_cookies_de($texto_headers);

    $datos = json_decode($texto_cuerpo, true);

    // Si la API no devolvió JSON válido, armamos una respuesta de error prolija
    if ($datos === null) {
        $datos = ['success' => false, 'error' => 'Respuesta inesperada del servidor'];
    }

    return [$codigo_http, $datos];
}

/**
 * Lee los headers "Set-Cookie" de la respuesta y los guarda en nuestra
 * propia sesión, para poder reenviarlos en la próxima petición.
 */
function guardar_cookies_de(string $texto_headers): void
{
    if (!preg_match_all('/^Set-Cookie:\s*([^;]+)/mi', $texto_headers, $coincidencias)) {
        return;
    }

    // Partimos de las cookies que ya teníamos guardadas (si había)
    $cookies = [];
    if (!empty($_SESSION['api_cookie'])) {
        foreach (explode('; ', $_SESSION['api_cookie']) as $par) {
            [$nombre] = explode('=', $par, 2);
            $cookies[$nombre] = $par;
        }
    }

    // Y las pisamos/sumamos con las nuevas que llegaron
    foreach ($coincidencias[1] as $par) {
        [$nombre] = explode('=', $par, 2);
        $cookies[$nombre] = $par;
    }

    $_SESSION['api_cookie'] = implode('; ', $cookies);
}

function api_get(string $path): array
{
    return api_call('GET', $path);
}

function api_post(string $path, array $body = []): array
{
    return api_call('POST', $path, $body);
}

function api_put(string $path, array $body = []): array
{
    return api_call('PUT', $path, $body);
}

function api_delete(string $path): array
{
    return api_call('DELETE', $path);
}

/**
 * Redirige a otra página y corta la ejecución.
 */
function redirigir(string $pagina): void
{
    header("Location: $pagina");
    exit;
}

/**
 * Corta la ejecución y manda al login si no hay un usuario logueado
 * en NUESTRA sesión de frontend.
 */
function exigir_login(): void
{
    if (empty($_SESSION['usuario'])) {
        redirigir('login.php');
    }
}
