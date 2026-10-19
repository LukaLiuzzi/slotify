# Slotify - Frontend (PHP + Docker)

Frontend en PHP que consume la API REST del backend (`../backend`).

```
frontend/
├── Dockerfile
├── docker-compose.yml
└── src/
    ├── config.php           cliente de la API (cURL) + sesión
    ├── helpers.php          formato de fechas/precios, avisos, fotos
    ├── partials/            head.php, foot.php, sidebar.php (se reutilizan)
    │
    ├── index.php            pantalla de inicio
    ├── login.php            POST /api/auth/login
    ├── olvide.php           POST /api/auth/forgot-password
    ├── register.php         POST /api/auth/register
    │
    ├── home.php             GET  /api/courts
    ├── reservar.php         GET  /api/courts (buscador y orden por precio)
    ├── disponibilidad.php   GET  /api/bookings/availability  +  POST /api/bookings
    ├── mis-reservas.php     GET  /api/bookings/my-bookings   +  POST /api/bookings/{id}/cancel
    ├── perfil.php           GET  /api/auth/me
    ├── logout.php           POST /api/auth/logout
    │
    ├── admin.php            panel de administrador (principal + tablas de canchas, reservas y usuarios)
    ├── admin_helpers.php    botones ver/editar/borrar, IDs (C0007) y estados
    ├── admin-ver.php        detalle de solo lectura
    ├── admin-editar.php     editar usuario (estado/desbloqueo) o reserva (estado)
    ├── admin-cancha.php     crear / editar cancha
    │
    ├── css/style.css
    └── images/              logo, fotos de canchas e iconos
```

## Cómo correrlo

1. Levantá primero el backend (carpeta `backend/`):
   ```
   docker-compose up -d --build
   ```
   Chequeá que `http://localhost:8080/api/courts` devuelva JSON.

2. Levantá el frontend (carpeta `frontend/`):
   ```
   docker-compose up -d --build
   ```

3. Abrí `http://localhost:8081`

   | Rol | Usuario | Contraseña |
   |---|---|---|
   | Administrador | `admin` | `Admin123!` |
   | Jugador | `jugador1` | `Jugador123!` |

Para apagar: `docker-compose down` (en cada carpeta).

Las tipografías (Abril Fatface, Saira Extra Condensed, Inter) se cargan desde
Google Fonts, así que hace falta internet. Sin conexión se ve igual, con
tipografías de reemplazo.

## Recuperar contraseña

No hay servidor de mails: el pedido del usuario queda como una solicitud que
ven los administradores.

1. En `olvide.php` ingresá el correo.
2. El pedido aparece en el panel de admin (`admin.php?seccion=cambios`).
3. El admin define la nueva contraseña ahí mismo y la informa al usuario.
   También puede descartar la solicitud sin hacer cambios.

## Cómo se conecta con el backend

El frontend corre en su propio contenedor, por eso la URL de la API no es
`localhost` (dentro del contenedor sería él mismo) sino
`http://host.docker.internal:8080`, que apunta a tu PC. Se define en
`docker-compose.yml` con la variable `API_BASE_URL`, y `src/config.php` la lee.
Sin Docker (por ejemplo con XAMPP) usa `http://localhost:8080` por defecto.

Las peticiones a la API las hace el servidor PHP con cURL, no el navegador.
Por eso `config.php` guarda la cookie de sesión de la API y el token CSRF dentro
de la sesión del frontend y los reenvía en cada pedido (patrón BFF).

## Qué muestra el diseño que la API todavía no tiene

| Diseño | Estado |
|---|---|
| Dirección, capacidad, servicios y estrellas de cada cancha | No están en `/api/courts`. Las tarjetas muestran nombre, tipo y precio. |
| Fotos de las canchas | Se asignan 3 fotos fijas según el id (`foto_cancha()` en `helpers.php`). |
| Perfil: nombre y apellido, residencia, foto, editar datos, cambiar contraseña | Sin endpoints. Los botones están desactivados ("Próximamente"). |
| Panel admin: "Solicitudes pendientes" | La API no tiene solicitudes; se muestran las cuentas deshabilitadas o bloqueadas. Los pedidos de recuperar contraseña no se pueden listar. |
| Tablas admin: "Ubicación" (canchas) y "Nombre" (usuarios) | No existen en la API; se usan Tipo de cancha / Precio y Tipo de cuenta / Estado. |
| Reservas: "borrar" | No hay endpoint para borrar; el botón cancela la reserva. |

## Botón "Atrás" después de cerrar sesión

Todas las páginas se envían con `Cache-Control: no-store`, el logout borra la cookie de sesión y, si el navegador muestra una página guardada, se recarga sola. Al recargar, el servidor no encuentra sesión y redirige al login con el mensaje "Necesitás iniciar sesión para ver esa página.".

| Recuperar contraseña pide nombre de usuario | La API solo usa el correo; el usuario no se envía. |
| Estado "Finalizada" | Se calcula en el frontend: reserva confirmada cuya hora ya pasó. |

Cuando el backend agregue esos campos, solo hay que sumar las líneas
correspondientes en las vistas.
