# Slotify - Frontend (PHP + Docker)

Frontend en PHP que consume la API REST del backend (`../backend`).

```
frontend/
├── Dockerfile
├── docker-compose.yml
├── .dockerignore
├── .gitignore
└── src/
    ├── config.php          -> cliente de la API (cURL) + sesión
    ├── index.php           -> bienvenida
    ├── login.php           -> POST /api/auth/login
    ├── register.php        -> POST /api/auth/register
    ├── home.php            -> GET /api/courts
    ├── disponibilidad.php  -> GET /api/bookings/availability + POST /api/bookings
    ├── logout.php          -> POST /api/auth/logout
    ├── images/logo.png
    └── css/style.css
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

3. Abrí `http://localhost:8081` y probá con `admin` / `Admin123!`
   o `jugador1` / `Jugador123!`.

Para apagar: `docker-compose down` (en cada carpeta).

## Cómo se conecta con el backend

El frontend corre en su propio contenedor, por eso la URL de la API no es
`localhost` (dentro del contenedor, `localhost` es el propio frontend) sino
`http://host.docker.internal:8080`, que apunta a tu PC. Se define en
`docker-compose.yml` con la variable `API_BASE_URL`, y `src/config.php` la lee.

Si corrés el frontend sin Docker (por ejemplo con XAMPP), no hace falta
configurar nada: usa `http://localhost:8080` por defecto.
