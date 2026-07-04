# Getting Started with LaravelCP

> A PBBG (Persistent Browser-Based Game) platform built with Laravel 11 + Vue 3.

---

## Prerequisites

- **Docker Desktop** or **OrbStack** (recommended on macOS)
- **Git**
- **Minimum 8GB RAM** recommended (the stack runs 4 containers)
- ~5 GB free disk space

---

## Quick Start (5 minutes)

```bash
# 1. Clone the repository
git clone https://github.com/Lindon11/LaravelCP.git
cd LaravelCP

# 2. Copy the backend environment file
cp backend/.env.example backend/.env

# 3. Build and start all Docker containers
docker compose up -d --build

# 4. Generate the Laravel application key
docker compose exec backend php artisan key:generate

# 5. Run database migrations
docker compose exec backend php artisan migrate --force

# 6. Install the application (creates admin user, roles, core data)
docker compose exec backend php artisan app:install --force \
    --admin-username=admin \
    --admin-email=admin@example.com \
    --admin-password=admin123

# 7. Setup license configuration
docker compose exec backend php artisan license:setup

# 8. Generate a development license (valid for all domains, never expires)
docker compose exec backend php artisan license:generate \
    --domain="*" \
    --tier=standard \
    --customer="Local Development" \
    --email="admin@example.com" \
    --expires=lifetime
```

Open **http://localhost:5175** in your browser.

---

## Step-by-Step Installation

### 1. Clone the Repository

```bash
git clone https://github.com/Lindon11/LaravelCP.git
cd LaravelCP
```

This downloads the full project, including the `frontend/` (Vue 3 + Vite) and `backend/` (Laravel 11 + PHP 8.3) directories plus the `docker-compose.yml` that ties everything together.

### 2. Configure the Environment

```bash
cp backend/.env.example backend/.env
```

The `.env` file contains database credentials, app URLs, and other configuration. The default values are pre-configured for Docker and should work out of the box. Key settings to be aware of:

| Setting | Default Value | Notes |
|---------|--------------|-------|
| `DB_HOST` | `mysql` | Docker service name, not localhost |
| `DB_DATABASE` | `laravelcp` | Matches docker-compose.yml |
| `DB_USERNAME` | `laravelcp` | Matches docker-compose.yml |
| `DB_PASSWORD` | `laravelcp` | Matches docker-compose.yml |
| `FRONTEND_URL` | `http://localhost:5173` | Update to `http://localhost:5175` if the frontend fails to connect |
| `CORS_ALLOWED_ORIGINS` | Includes `localhost:5175` | Good to go |

> **Note:** The `docker-entrypoint.sh` inside the container can auto-create `.env` from `.env.example` on first boot if you skip this step. But doing it manually beforehand is cleaner.

### 3. Build and Start Containers

```bash
docker compose up -d --build
```

This starts 4 services in the background:

| Service | Container Name | Purpose |
|---------|---------------|---------|
| `backend` | `laravelcp_backend` | Laravel 11 + PHP 8.3 + Apache |
| `frontend` | `laravelcp_frontend` | Vue 3 + Vite dev server (hot reload) |
| `mysql` | `laravelcp_db` | MySQL 8.0 database |
| `phpmyadmin` | `laravelcp_pma` | Database management UI |

On first boot, the backend's `docker-entrypoint.sh` automatically:
- Generates an `.env` file if missing
- Runs `php artisan key:generate`
- Sets storage permissions
- Waits for MySQL to become healthy
- Runs `php artisan app:install` (if `storage/installed` does not exist)

Wait a moment for the containers to initialize. You can watch progress with:

```bash
docker compose logs -f
```

Look for lines like `Database ready — running installer...` to confirm the backend is working.

### 4. Generate the Application Key

```bash
docker compose exec backend php artisan key:generate
```

This sets `APP_KEY` in your `.env` file — a required encryption key Laravel uses for session data, cookie encryption, and other security-sensitive operations. If the entrypoint script already ran this, the command is idempotent and harmless.

### 5. Run Database Migrations

```bash
docker compose exec backend php artisan migrate --force
```

Migrations create all the database tables LaravelCP needs: users, roles, plugins, sessions, and game-specific tables. The `--force` flag suppresses the confirmation prompt (needed since we're in a non-interactive environment).

### 6. Install the Application

```bash
docker compose exec backend php artisan app:install --force \
    --admin-username=admin \
    --admin-email=admin@example.com \
    --admin-password=admin123
```

This custom command seeds the database with:
- The **admin user** account (login credentials as specified)
- **Default roles and permissions** (admin, moderator, player, etc.)
- **Core system settings** (game configuration defaults)
- **Plugin registry entries** (available game feature plugins)

The `--force` flag allows the command to run even if the installer detects an existing installation.

### 7. Setup License Configuration

```bash
docker compose exec backend php artisan license:setup
```

Prepares the license system by creating the necessary database tables and configuration entries. This is a one-time setup step that enables the license validation layer.

### 8. Generate a Development License

```bash
docker compose exec backend php artisan license:generate \
    --domain="*" \
    --tier=standard \
    --customer="Local Development" \
    --email="admin@example.com" \
    --expires=lifetime
```

LaravelCP requires a license to operate. For local development, generate a license that:
- **`--domain="*"`** — Works on any domain (localhost, 127.0.0.1, etc.)
- **`--tier=standard`** — Grants full access to all features
- **`--expires=lifetime`** — Never expires

In production you would generate a domain-specific license with a set expiry date.

---

## Access Points

| Service | URL | Description |
|---------|-----|-------------|
| **Frontend** | http://localhost:5175 | Vue 3 dev server with hot module replacement |
| **Backend API** | http://localhost:8001 | REST API (Laravel) |
| **Admin Panel** | http://localhost:8001/admin | Admin dashboard |
| **API Docs** | http://localhost:8001/docs | Scribe auto-generated API documentation |
| **phpMyAdmin** | http://localhost:8082 | MySQL database management |

---

## Default Credentials

| Account | Username / Email | Password |
|---------|-----------------|----------|
| **Admin** | `admin` / `admin@example.com` | `admin123` |
| **MySQL root** | `root` | `root` |
| **MySQL app user** | `laravelcp` | `laravelcp` |

**Change the admin password before going to production.**

---

## Verification

Run these checks to confirm everything is working:

### 1. Check that containers are running

```bash
docker compose ps
```

All 4 services should show `Up` or `healthy`.

### 2. Test the backend API

```bash
curl -s http://localhost:8001 | head -20
```

You should get a JSON response or a Laravel redirect (not a connection refused error).

### 3. Test a specific endpoint

```bash
curl -s http://localhost:8001/api/v1/dashboard
```

### 4. Test the admin panel

```bash
curl -s -o /dev/null -w "%{http_code}" http://localhost:8001/admin
```

Should return `200`.

### 5. Log in through the API

```bash
curl -s -X POST http://localhost:8001/api/v1/login \
    -H "Content-Type: application/json" \
    -H "Accept: application/json" \
    -d '{"email": "admin@example.com", "password": "admin123"}'
```

A successful response includes an authentication token.

### 6. Check the frontend is serving

```bash
curl -s -o /dev/null -w "%{http_code}" http://localhost:5175
```

Should return `200`.

---

## Common Docker Commands

```bash
# View running containers and their status
docker compose ps

# Tail logs from all services (Ctrl+C to exit)
docker compose logs -f

# Tail logs from a specific service
docker compose logs -f backend
docker compose logs -f frontend
docker compose logs -f mysql

# Run a command inside the backend container
docker compose exec backend php artisan list

# Open a shell in the backend container
docker compose exec backend bash

# Open a shell in the frontend container
docker compose exec frontend sh

# Restart a single service
docker compose restart backend

# Stop and remove containers (preserves database volume)
docker compose down

# Stop, remove containers, AND delete the database volume
docker compose down -v

# Rebuild containers from scratch (no cache)
docker compose build --no-cache

# Rebuild and start
docker compose up -d --build
```

---

## Troubleshooting

### Port Conflicts

**Symptom:** `Error response from daemon: port is already allocated`

One or more required ports (8001, 5175, 3307, 8082) are already in use.

**Solution:**

```bash
# Find what's using a port
lsof -i :8001

# Stop the conflicting process, or change the host port in docker-compose.yml:
#   ports:
#     - "8002:80"   # change 8001 to 8002
```

Then re-run `docker compose up -d`.

### Database Connection Issues

**Symptom:** `SQLSTATE[HY000] [2002] Connection refused` or `phpmyadmin #2002 Cannot log in to the MySQL server`

MySQL takes time to initialize on first boot. The backend container waits for it automatically, but if you see this error:

```bash
# Check if MySQL is healthy
docker compose ps

# Check MySQL logs
docker compose logs mysql

# If MySQL is still starting, wait and retry
docker compose exec backend php artisan migrate --force
```

### License Validation Failed

**Symptom:** The application shows a license error or blocks access.

```bash
# Re-run license setup and generate a new license
docker compose exec backend php artisan license:setup
docker compose exec backend php artisan license:generate \
    --domain="*" \
    --tier=standard \
    --customer="Local Development" \
    --email="admin@example.com" \
    --expires=lifetime
```

### Old Data Persisting

**Symptom:** You see stale data from a previous installation after rebuilding.

The MySQL volume (`mysql_data`) persists across `docker compose down`. To completely reset:

```bash
# WARNING: This deletes all database data
docker compose down -v
docker compose up -d --build
```

### Permission Issues

**Symptom:** `file_put_contents(...): Failed to open stream: Permission denied` or a white screen

The backend container's `docker-entrypoint.sh` automatically fixes storage permissions on boot. If issues persist:

```bash
docker compose exec backend chmod -R 775 storage bootstrap/cache
docker compose exec backend chown -R www-data:www-data storage bootstrap/cache
```

### Backend Container Exits Immediately

**Symptom:** `docker compose ps` shows the backend as `Exit`.

```bash
# Check what went wrong
docker compose logs backend
```

Common causes:
- The Apache document root or config is misconfigured — check `APACHE_DOCUMENT_ROOT` environment variable
- A PHP extension is missing — check the Dockerfile
- The entrypoint script crashed — check for syntax errors in `.env`

### Frontend Hot Reload Not Working

**Symptom:** Changes to Vue files don't trigger a browser refresh.

The Vite dev server uses WebSocket for HMR. Ensure `clientPort: 5175` in `vite.config.ts` matches the mapped port. If behind a reverse proxy, additional configuration may be needed.

---

## Using OrbStack (macOS)

If you use [OrbStack](https://orbstack.dev) instead of Docker Desktop:

1. **Install OrbStack** — download from orbstack.dev (it replaces Docker Desktop entirely)
2. **No Docker Desktop needed** — OrbStack provides a drop-in Docker-compatible environment
3. **Run the same commands** — all `docker compose` commands work identically
4. **Performance benefits** — OrbStack is significantly faster and lighter than Docker Desktop on macOS

OrbStack also provides **`.orb.local` domains** for easy access to containers. After starting the stack, you can optionally access services at:
- `http://laravelcp-backend.orb.local:8001`
- `http://laravelcp-frontend.orb.local:5175`

This is handled automatically — no configuration required on your part.

---

## Next Steps

- Browse the **admin panel** at http://localhost:8001/admin (login with admin / admin123)
- Explore the **API documentation** at http://localhost:8001/docs
- Read [INTEGRATION_GUIDE.md](./INTEGRATION_GUIDE.md) for frontend-backend integration details
- Read [DEPLOYMENT.md](./DEPLOYMENT.md) when you're ready for production
- Check [backend/docs/PLUGIN_HOOKS.md](./backend/docs/PLUGIN_HOOKS.md) for plugin development
