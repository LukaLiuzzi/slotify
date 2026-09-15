# Documentación Técnica de la API REST - Slotify (Pádel)

Backend RESTful desarrollado en PHP 8.4 orientado a objetos (POO) con persistencia en MySQL 9.0 y contenedorizado con Docker.

---

## 1. Información General y Autenticación

- **Base URL local:** `http://localhost:8080`
- **Formato de intercambio:** `application/json` en todas las peticiones con cuerpo y respuestas.
- **Manejo de Sesión:**
  - El sistema utiliza sesiones nativas de PHP basadas en Cookies seguras (`PHPSESSID`).
  - La cookie se envía automáticamente con flags `HttpOnly` y `SameSite=Strict`.
  - Al realizar peticiones desde Postman o navegadores, se debe preservar la cookie recibida tras el inicio de sesión (`POST /api/auth/login`).
- **Protección CSRF:**
  - Para peticiones de escritura (`POST`, `PUT`, `DELETE`) en rutas protegidas, se debe enviar el header `X-CSRF-Token` con el valor del token CSRF provisto en el login, en `GET /api/auth/me` o en `GET /api/csrf-token`.

### Estructura Estándar de Respuestas

#### Respuesta Exitosa
```json
{
  "success": true,
  "message": "Mensaje descriptivo opcional",
  "data": {}
}
```

#### Respuesta de Error
```json
{
  "success": false,
  "error": "Mensaje descriptivo del error",
  "errors": {}
}
```

### Códigos de Estado HTTP Utilizados
- `200 OK`: Petición procesada exitosamente.
- `201 Created`: Recurso creado exitosamente.
- `400 Bad Request`: Datos de entrada faltantes o con formato inválido.
- `401 Unauthorized`: Usuario no autenticado o credenciales incorrectas.
- `403 Forbidden`: Acceso denegado (requiere permisos de administrador, cuenta deshabilitada o token CSRF inválido).
- `404 Not Found`: Recurso o ruta no encontrada.
- `409 Conflict`: Conflicto de recursos (ej: nombre de usuario o email ya registrado, o turno ya reservado en ese horario).
- `429 Too Many Requests`: Cuenta temporalmente bloqueada tras 5 intentos fallidos consecutivos de login.
- `500 Internal Server Error`: Error inesperado en el servidor o base de datos.

---

## 2. Usuarios Iniciales (Seed Data)

| Rol | Usuario | Email | Contraseña |
|---|---|---|---|
| Administrador | `admin` | `admin@slotify.com` | `Admin123!` |
| Jugador (Cliente) | `jugador1` | `jugador1@slotify.com` | `Jugador123!` |

---

## 3. Endpoints de la API

### 3.1. Autenticación y Seguridad

#### 3.1.1. Obtener Token CSRF
- **Método:** `GET`
- **Ruta:** `/api/csrf-token`
- **Acceso:** Público
- **Respuesta Exitosa (200 OK):**
```json
{
  "success": true,
  "data": {
    "csrf_token": "a1b2c3d4e5f6..."
  }
}
```

---

#### 3.1.2. Registro de Usuario
- **Método:** `POST`
- **Ruta:** `/api/auth/register`
- **Acceso:** Público
- **Headers requeridos:** `Content-Type: application/json`
- **Cuerpo de la Petición (Request Body):**
```json
{
  "username": "carlos_padel",
  "email": "carlos@example.com",
  "password": "Password123!"
}
```
- **Reglas de Validación:**
  - `username`: Entre 3 y 50 caracteres alfanuméricos y guión bajo. Debe ser único.
  - `email`: Formato de correo electrónico válido. Debe ser único.
  - `password`: Mínimo 8 caracteres, al menos una mayúscula, un número y un símbolo.
- **Respuesta Exitosa (201 Created):**
```json
{
  "success": true,
  "message": "Usuario registrado exitosamente",
  "data": {
    "id": 3,
    "username": "carlos_padel",
    "email": "carlos@example.com",
    "role": "usuario"
  }
}
```
- **Respuesta de Error (400 Bad Request):**
```json
{
  "success": false,
  "error": "La contraseña debe tener al menos 8 caracteres, incluir una letra mayúscula, un número y un símbolo"
}
```

---

#### 3.1.3. Inicio de Sesión (Login)
- **Método:** `POST`
- **Ruta:** `/api/auth/login`
- **Acceso:** Público
- **Headers requeridos:** `Content-Type: application/json`
- **Cuerpo de la Petición (Request Body):**
```json
{
  "username": "admin",
  "password": "Admin123!"
}
```
*(También se puede enviar `"email"` en lugar de `"username"`)*
- **Comportamiento de Seguridad:**
  - Si se ingresan credenciales erróneas 5 veces consecutivas, la cuenta queda bloqueada por 15 minutos (código HTTP `429`).
  - Cada intento genera un registro de auditoría (`audit_logs`) con IP y User Agent.
  - Al iniciar sesión exitosamente se regenera el ID de sesión para prevenir *Session Fixation*.
- **Respuesta Exitosa (200 OK):**
```json
{
  "success": true,
  "message": "Inicio de sesión exitoso",
  "data": {
    "id": 1,
    "username": "admin",
    "email": "admin@slotify.com",
    "role": "admin",
    "csrf_token": "a1b2c3d4..."
  }
}
```
- **Respuesta de Error - Credenciales Inválidas (401 Unauthorized):**
```json
{
  "success": false,
  "error": "Credenciales inválidas"
}
```
- **Respuesta de Error - Bloqueo por Fuerza Bruta (429 Too Many Requests):**
```json
{
  "success": false,
  "error": "La cuenta se encuentra temporalmente bloqueada por reiterados intentos fallidos. Intente nuevamente en unos minutos"
}
```

---

#### 3.1.4. Obtener Perfil del Usuario Autenticado
- **Método:** `GET`
- **Ruta:** `/api/auth/me`
- **Acceso:** Autenticado
- **Respuesta Exitosa (200 OK):**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "username": "admin",
    "email": "admin@slotify.com",
    "role": "admin",
    "is_active": true,
    "csrf_token": "a1b2c3d4..."
  }
}
```

---

#### 3.1.5. Cierre de Sesión (Logout)
- **Método:** `POST`
- **Ruta:** `/api/auth/logout`
- **Acceso:** Público / Autenticado
- **Respuesta Exitosa (200 OK):**
```json
{
  "success": true,
  "message": "Sesión finalizada correctamente"
}
```

---

#### 3.1.6. Solicitar Recuperación de Contraseña
- **Método:** `POST`
- **Ruta:** `/api/auth/forgot-password`
- **Acceso:** Público
- **Cuerpo de la Petición:**
```json
{
  "email": "jugador1@slotify.com"
}
```
- **Respuesta Exitosa (200 OK):**
```json
{
  "success": true,
  "message": "Si el correo electrónico se encuentra registrado, se enviaron las instrucciones para restablecer la contraseña"
}
```
- **Nota de Seguridad y Entorno de Desarrollo:**
  - Por seguridad y prevención de *User Enumeration* y secuestro de cuentas (*Account Takeover*), la API pública **nunca** devuelve el token en la respuesta HTTP.
  - En un entorno de producción, el token se envía al correo del usuario.
  - Para pruebas en este entorno local/Docker, el backend registra el token en los logs del servidor (`docker logs tp_php_app`) simulando la salida del servicio de emails.

---

#### 3.1.7. Restablecer Contraseña con Token
- **Método:** `POST`
- **Ruta:** `/api/auth/reset-password`
- **Acceso:** Público
- **Cuerpo de la Petición:**
```json
{
  "token": "4e7a8f1b90...",
  "password": "NuevoPassword123!"
}
```
- **Respuesta Exitosa (200 OK):**
```json
{
  "success": true,
  "message": "Contraseña actualizada correctamente. Ya puede iniciar sesión"
}
```

---

### 3.2. Canchas de Pádel (`/api/courts`)

#### 3.2.1. Listar Canchas
- **Método:** `GET`
- **Ruta:** `/api/courts`
- **Acceso:** Público (los administradores pueden agregar `?active_only=0` para ver canchas inactivas).
- **Respuesta Exitosa (200 OK):**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "Cancha Central",
      "court_type": "Cristal Panorámica Techada",
      "price_per_hour": "15000.00",
      "is_active": 1,
      "created_at": "2026-09-08 22:00:00"
    }
  ]
}
```

---

#### 3.2.2. Ver Detalle de Cancha
- **Método:** `GET`
- **Ruta:** `/api/courts/{id}`
- **Acceso:** Público
- **Respuesta Exitosa (200 OK):**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "name": "Cancha Central",
    "court_type": "Cristal Panorámica Techada",
    "price_per_hour": "15000.00",
    "is_active": 1,
    "created_at": "2026-09-08 22:00:00"
  }
}
```

---

#### 3.2.3. Crear Cancha (Admin)
- **Método:** `POST`
- **Ruta:** `/api/admin/courts`
- **Acceso:** Administrador
- **Headers:** `X-CSRF-Token: <token>`
- **Cuerpo de la Petición:**
```json
{
  "name": "Cancha 4 - Césped Azul",
  "court_type": "Cristal Outdoor Pro",
  "price_per_hour": 14000.00,
  "is_active": true
}
```
- **Respuesta Exitosa (201 Created):**
```json
{
  "success": true,
  "message": "Cancha creada exitosamente",
  "data": {
    "id": 4,
    "name": "Cancha 4 - Césped Azul",
    "court_type": "Cristal Outdoor Pro",
    "price_per_hour": "14000.00",
    "is_active": 1
  }
}
```

---

#### 3.2.4. Modificar Cancha (Admin)
- **Método:** `PUT`
- **Ruta:** `/api/admin/courts/{id}`
- **Acceso:** Administrador
- **Headers:** `X-CSRF-Token: <token>`
- **Cuerpo de la Petición:**
```json
{
  "name": "Cancha Central Renovada",
  "court_type": "Cristal Panorámica Techada WPT",
  "price_per_hour": 18000.00,
  "is_active": true
}
```
- **Respuesta Exitosa (200 OK):**
```json
{
  "success": true,
  "message": "Cancha actualizada exitosamente",
  "data": {
    "id": 1,
    "name": "Cancha Central Renovada",
    "court_type": "Cristal Panorámica Techada WPT",
    "price_per_hour": "18000.00",
    "is_active": 1
  }
}
```

---

#### 3.2.5. Eliminar / Desactivar Cancha (Admin)
- **Método:** `DELETE`
- **Ruta:** `/api/admin/courts/{id}`
- **Acceso:** Administrador
- **Headers:** `X-CSRF-Token: <token>`
- **Respuesta Exitosa (200 OK):**
```json
{
  "success": true,
  "message": "Cancha dada de baja exitosamente"
}
```

---

### 3.3. Turnos y Reservas (`/api/bookings`)

#### 3.3.1. Consultar Disponibilidad de Turnos de 60 Minutos
- **Método:** `GET`
- **Ruta:** `/api/bookings/availability?court_id={id}&date={YYYY-MM-DD}`
- **Acceso:** Público
- **Ejemplo de Consulta:** `/api/bookings/availability?court_id=1&date=2026-09-10`
- **Respuesta Exitosa (200 OK):**
```json
{
  "success": true,
  "data": {
    "court": {
      "id": 1,
      "name": "Cancha Central",
      "price_per_hour": 15000
    },
    "date": "2026-09-10",
    "slots": [
      {
        "start_time": "08:00:00",
        "end_time": "09:00:00",
        "label": "08:00 - 09:00",
        "is_available": true
      },
      {
        "start_time": "18:00:00",
        "end_time": "19:00:00",
        "label": "18:00 - 19:00",
        "is_available": false
      }
    ]
  }
}
```

---

#### 3.3.2. Reservar un Turno
- **Método:** `POST`
- **Ruta:** `/api/bookings`
- **Acceso:** Usuario autenticado
- **Headers:** `X-CSRF-Token: <token>`
- **Cuerpo de la Petición:**
```json
{
  "court_id": 1,
  "booking_date": "2026-09-15",
  "start_time": "19:00",
  "end_time": "20:00"
}
```
- **Control de Concurrencia:**
  - Ejecuta una transacción SQL con bloqueo `FOR UPDATE`.
  - Si dos usuarios intentan reservar el mismo horario simultáneamente, la base de datos garantiza que solo uno lo logre y el segundo reciba `409 Conflict`.
- **Respuesta Exitosa (201 Created):**
```json
{
  "success": true,
  "message": "Turno reservado exitosamente",
  "data": {
    "id": 2,
    "court_id": 1,
    "user_id": 2,
    "booking_date": "2026-09-15",
    "start_time": "19:00:00",
    "end_time": "20:00:00",
    "status": "confirmada",
    "court_name": "Cancha Central",
    "price_per_hour": "15000.00",
    "username": "jugador1",
    "email": "jugador1@slotify.com"
  }
}
```
- **Respuesta de Conflicto (409 Conflict):**
```json
{
  "success": false,
  "error": "El turno seleccionado ya se encuentra reservado"
}
```

---

#### 3.3.3. Mis Reservas (Cliente)
- **Método:** `GET`
- **Ruta:** `/api/bookings/my-bookings`
- **Acceso:** Usuario autenticado
- **Respuesta Exitosa (200 OK):**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "court_id": 1,
      "user_id": 2,
      "booking_date": "2026-09-10",
      "start_time": "18:00:00",
      "end_time": "19:00:00",
      "status": "confirmada",
      "court_name": "Cancha Central",
      "court_type": "Cristal Panorámica Techada",
      "price_per_hour": "15000.00"
    }
  ]
}
```

---

#### 3.3.4. Cancelar Turno Propio
- **Método:** `POST`
- **Ruta:** `/api/bookings/{id}/cancel`
- **Acceso:** Usuario autenticado (dueño del turno o administrador)
- **Headers:** `X-CSRF-Token: <token>`
- **Regla:** No se permite cancelar turnos que correspondan a fechas u horas pasadas.
- **Respuesta Exitosa (200 OK):**
```json
{
  "success": true,
  "message": "Reserva cancelada correctamente"
}
```

---

#### 3.3.5. Listar Todas las Reservas (Admin)
- **Método:** `GET`
- **Ruta:** `/api/admin/bookings`
- **Acceso:** Administrador
- **Parámetros de consulta opcionales:**
  - `?date=2026-09-10`
  - `?court_id=1`
  - `?status=confirmada` (o `cancelada`)
- **Respuesta Exitosa (200 OK):**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "court_id": 1,
      "user_id": 2,
      "booking_date": "2026-09-10",
      "start_time": "18:00:00",
      "end_time": "19:00:00",
      "status": "confirmada",
      "court_name": "Cancha Central",
      "username": "jugador1",
      "email": "jugador1@slotify.com"
    }
  ]
}
```

---

#### 3.3.6. Cambiar Estado de Reserva (Admin)
- **Método:** `POST`
- **Ruta:** `/api/admin/bookings/{id}/status`
- **Acceso:** Administrador
- **Headers:** `X-CSRF-Token: <token>`
- **Cuerpo de la Petición:**
```json
{
  "status": "cancelada"
}
```
- **Respuesta Exitosa (200 OK):**
```json
{
  "success": true,
  "message": "Estado de la reserva actualizado",
  "data": {
    "id": 1,
    "status": "cancelada"
  }
}
```

---

### 3.4. Administración de Usuarios y Auditoría

#### 3.4.1. Listar Usuarios y Búsqueda por Email (Admin)
- **Método:** `GET`
- **Ruta:** `/api/admin/users`
- **Acceso:** Administrador
- **Parámetros de consulta opcionales:**
  - `?email=slotify.com` (Buscador por email exigido por el TP)
  - `?username=jugador`
- **Respuesta Exitosa (200 OK):**
```json
{
  "success": true,
  "data": [
    {
      "id": 2,
      "username": "jugador1",
      "email": "jugador1@slotify.com",
      "role": "usuario",
      "is_active": 1,
      "failed_login_attempts": 0,
      "locked_until": null,
      "created_at": "2026-09-08 22:00:00"
    }
  ]
}
```

---

#### 3.4.2. Activar, Bloquear o Desbloquear Cuenta (Admin)
- **Método:** `PUT`
- **Ruta:** `/api/admin/users/{id}/status`
- **Acceso:** Administrador
- **Headers:** `X-CSRF-Token: <token>`
- **Cuerpo de la Petición:**
```json
{
  "is_active": 1,
  "unlock": true
}
```
- **Respuesta Exitosa (200 OK):**
```json
{
  "success": true,
  "message": "Estado del usuario actualizado correctamente",
  "data": {
    "id": 2,
    "username": "jugador1",
    "is_active": 1,
    "locked_until": null
  }
}
```

---

#### 3.4.3. Eliminar Usuario (Admin)
- **Método:** `DELETE`
- **Ruta:** `/api/admin/users/{id}`
- **Acceso:** Administrador
- **Headers:** `X-CSRF-Token: <token>`
- **Regla:** Un administrador no puede autoeliminarse.
- **Respuesta Exitosa (200 OK):**
```json
{
  "success": true,
  "message": "Usuario eliminado correctamente"
}
```

---

#### 3.4.4. Listado de Logs de Auditoría (Admin)
- **Método:** `GET`
- **Ruta:** `/api/admin/audit-logs`
- **Acceso:** Administrador
- **Parámetros de consulta opcionales:**
  - `?limit=20`
  - `?offset=0`
  - `?username=admin`
  - `?action=LOGIN_FAILED`
  - `?status=failure`
- **Respuesta Exitosa (200 OK):**
```json
{
  "success": true,
  "data": {
    "limit": 20,
    "offset": 0,
    "logs": [
      {
        "id": 5,
        "user_id": 2,
        "username": "jugador1",
        "action": "LOGIN_SUCCESS",
        "ip_address": "127.0.0.1",
        "user_agent": "Mozilla/5.0...",
        "status": "success",
        "details": "Inicio de sesión exitoso",
        "created_at": "2026-09-08 22:15:30"
      },
      {
        "id": 4,
        "user_id": 2,
        "username": "jugador1",
        "action": "LOGIN_FAILED",
        "ip_address": "127.0.0.1",
        "user_agent": "Mozilla/5.0...",
        "status": "failure",
        "details": "Contraseña incorrecta. Intento 1",
        "created_at": "2026-09-08 22:15:10"
      }
    ]
  }
}
```
