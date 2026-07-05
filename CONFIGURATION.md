# LaravelCP Configuration Reference

> **Project**: LaravelCP — A Laravel 11 / Vue 3 Persistent Browser-Based Game (PBBG) platform.

---

## Table of Contents

1. [Environment Basics](#1-environment-basics)
2. [Application Config (`backend/config/app.php`)](#2-application-config-backendconfigappphp)
3. [Authentication & Sanctum](#3-authentication--sanctum)
4. [CORS](#4-cors)
5. [Database](#5-database)
6. [Redis / Cache](#6-redis--cache)
7. [Session](#7-session)
8. [Queue](#8-queue)
9. [Mail](#9-mail)
10. [Logging](#10-logging)
11. [Filesystem](#11-filesystem)
12. [Plugin System](#12-plugin-system)
13. [OAuth / Services](#13-oauth--services)
14. [Permissions (Spatie)](#14-permissions-spatie)
15. [Scribe (API Docs)](#15-scribe-api-docs)
16. [Emojis](#16-emojis)
17. [Docker Services](#17-docker-services)
18. [Frontend (Vite / Vue 3)](#18-frontend-vite--vue-3)
19. [License Configuration](#19-license-configuration)
20. [Environment-Specific Recommendations](#20-environment-specific-recommendations)

---

## 1. Environment Basics

The `.env` file at `backend/.env` drives nearly every runtime behaviour. Copy the example file and customise:

```bash
cp backend/.env.example backend/.env
```

### Quick Reference Table

| Variable | Default | Local Dev | Production |
|---|---|---|---|
| `APP_NAME` | `LaravelCP` | `LaravelCP` | Your brand name |
| `APP_ENV` | `local` | `local` | `production` |
| `APP_DEBUG` | `true` | `true` | `false` |
| `APP_URL` | `http://localhost` | `http://localhost:8001` | `https://your-domain.com` |
| `FRONTEND_URL` | `http://localhost:5173` | `http://localhost:5175` | `https://your-domain.com` |
| `DB_HOST` | `mysql` | `127.0.0.1` or docker service name | Production DB host |
| `DB_DATABASE` | `laravelcp` | `laravelcp` | Production DB name |
| `SESSION_DRIVER` | `database` | `database` | `database` or `redis` |
| `SESSION_ENCRYPT` | `false` | `false` | `true` |
| `QUEUE_CONNECTION` | `database` | `database` | `redis` |
| `CACHE_STORE` | `database` | `database` | `redis` |
| `MAIL_MAILER` | `log` | `log` | `smtp` or `mailgun` |
| `SANCTUM_STATEFUL_DOMAINS` | `localhost,...` | Frontend URLs | Production domain |
| `CORS_ALLOWED_ORIGINS` | `http://localhost:5173,...` | Same | Production origin |

---

## 2. Application Config (`backend/config/app.php`)

### `name`
- **Env**: `APP_NAME`
- **Default**: `Laravel`
- **When to change**: Set to your game's brand name. Used in notifications, emails, and UI labels.

### `env`
- **Env**: `APP_ENV`
- **Default**: `production`
- **Values**: `local`, `production`, `testing`, `staging`
- **When to change**: Set `local` during development. Controls which config caches are loaded and error verbosity.

### `debug`
- **Env**: `APP_DEBUG`
- **Default**: `false`
- **When to change**: `true` for local development (detailed error pages). **Always `false` in production** — exposes full stack traces and env values to users.

### `url`
- **Env**: `APP_URL`
- **Default**: `http://localhost`
- **When to change**: The canonical backend URL. Used by Artisan commands for URL generation. Must match your deployment domain in production.

### `frontend_url`
- **Env**: `FRONTEND_URL`
- **Default**: `http://localhost:5173`
- **When to change**: The SPA frontend URL. Used to generate password reset links and other frontend-facing redirects.

### `timezone`
- **Env**: `APP_TIMEZONE`
- **Default**: `UTC`
- **When to change**: Set to your preferred timezone (e.g. `America/New_York`, `Europe/London`) if your game operates in a specific timezone.

### `locale`, `fallback_locale`, `faker_locale`
- **Env**: `APP_LOCALE`, `APP_FALLBACK_LOCALE`, `APP_FAKER_LOCALE`
- **Default**: `en`, `en`, `en_US`
- **When to change**: Localise the application. Faker locale changes how test/seed data is generated (names, addresses, etc).

### `key`
- **Env**: `APP_KEY`
- **Default**: none (Laravel generates a 32-char base64 key)
- **When to change**: **Required.** Generate with `php artisan key:generate`. Changing it invalidates all encrypted data (sessions, cookies, etc).

### `cipher`
- **Hardcoded**: `AES-256-CBC`
- **When to change**: Only if you need AES-128-GCM compatibility. Not recommended.

### `maintenance.driver`
- **Env**: `APP_MAINTENANCE_DRIVER`
- **Default**: `file`
- **Options**: `file`, `cache`
- **When to change**: Use `cache` if you run multiple backend instances and need maintenance mode to apply globally.

---

## 3. Authentication & Sanctum

### `backend/config/auth.php`

#### Default Guard & Password Broker
```php
'guard' => env('AUTH_GUARD', 'sanctum'),
'passwords' => env('AUTH_PASSWORD_BROKER', 'users'),
```
- Sanctum is the default guard for API token authentication.
- Password reset broker uses the `users` provider.

#### Guards
| Guard | Driver | Provider | Use Case |
|---|---|---|---|
| `web` | `session` | `users` | Admin panel session auth (if used) |
| `sanctum` | `sanctum` | `users` | API token / SPA cookie auth |

#### Password Reset
- `expire`: 60 minutes
- `throttle`: 60 seconds

### `backend/config/sanctum.php`

| Setting | Value | Env | Notes |
|---|---|---|---|
| `stateful` | Auto-computed from `SANCTUM_STATEFUL_DOMAINS` | `SANCTUM_STATEFUL_DOMAINS` | SPA domains that receive stateful cookie authentication |
| `guard` | `['web']` | — | Fallback guards checked alongside bearer tokens |
| `expiration` | 10080 min (7 days) | `SANCTUM_TOKEN_EXPIRATION` | Prevents leaked tokens from being valid forever |
| `token_prefix` | `lcp_` | `SANCTUM_TOKEN_PREFIX` | Helps GitHub secret scanning identify committed tokens |
| `middleware` | AuthenticateSession, EncryptCookies, ValidateCsrfToken | — | Sanctum's SPA middleware stack |

**Important for production**: Add your frontend domain to `SANCTUM_STATEFUL_DOMAINS` so SPA cookie auth works. For local dev with the default compose setup, this is already `localhost:5175`.

---

## 4. CORS

**File**: `backend/config/cors.php`

| Setting | Default | Env | Notes |
|---|---|---|---|
| `paths` | `['api/*', 'sanctum/csrf-cookie', 'livewire/*']` | — | Which routes receive CORS headers |
| `allowed_methods` | GET, POST, PUT, PATCH, DELETE, OPTIONS | — | All standard HTTP methods |
| `allowed_origins` | `http://localhost:5175,http://localhost:8001` | `CORS_ALLOWED_ORIGINS` | Comma-separated list** |
| `supports_credentials` | `false` | `CORS_SUPPORTS_CREDENTIALS` | Set `true` if using cookie-based SPA auth |
| `max_age` | 86400 (24h) | — | Cache preflight responses |
| `allowed_headers` | Content-Type, Authorization, X-Requested-With, X-XSRF-TOKEN, Accept | — | Standard headers |
| `exposed_headers` | X-RateLimit-Limit, X-RateLimit-Remaining | — | Rate-limit headers exposed to the client |

**Production**: Set `CORS_ALLOWED_ORIGINS` to your specific frontend domain(s). Never use `*` when `supports_credentials` is `true`.

---

## 5. Database

**File**: `backend/config/database.php`

### Default Connection
```php
'default' => env('DB_CONNECTION', 'sqlite'),
```
- `.env.example` sets `DB_CONNECTION=mysql`.
- Production should always use MySQL or MariaDB.

### MySQL Connection (`connections.mysql`)

| Env Variable | Default | Notes |
|---|---|---|
| `DB_HOST` | `127.0.0.1` | Docker service name `mysql` in compose |
| `DB_PORT` | `3306` | Mapped to `3307` on host in docker-compose |
| `DB_DATABASE` | `laravel` | Set to `laravelcp` in `.env.example` |
| `DB_USERNAME` | `root` | Set to `laravelcp` in docker-compose |
| `DB_PASSWORD` | empty | Set to `laravelcp` in docker-compose |
| `DB_CHARSET` | `utf8mb4` | Full Unicode support (emojis, special chars) |
| `DB_COLLATION` | `utf8mb4_unicode_ci` | Case-insensitive Unicode sorting |
| `MYSQL_ATTR_SSL_CA` | — | SSL CA path for secure connections |

### Other Supported Drivers
- `sqlite` — file-based, for testing
- `mariadb` — drop-in MySQL replacement
- `pgsql` — PostgreSQL
- `sqlsrv` — SQL Server

### Migration Table
- Table: `migrations`
- `update_date_on_publish`: `true`

---

## 6. Redis / Cache

### Cache (`backend/config/cache.php`)

#### Default Store
```php
'default' => env('CACHE_STORE', 'database'),
```

#### Available Stores

| Store | Driver | Best For |
|---|---|---|
| `array` | memory | Unit tests (non-persistent, instant) |
| `database` | MySQL | Simple setups (default) |
| `file` | filesystem | Single-server dev |
| `redis` | Redis | **Production** — fast, supports locks, tags |
| `memcached` | Memcached | Alternative in-memory cache |
| `dynamodb` | AWS DynamoDB | AWS-native deployments |
| `octane` | Laravel Octane | Octane memory persistence |

**Production recommendation**: Set `CACHE_STORE=redis` for performance and to avoid database contention under game load.

#### Cache Prefix
- **Env**: `CACHE_PREFIX`
- **Default**: `{APP_NAME}_cache_`

### Redis (`config/database.php` → `redis`)

| Env Variable | Default | Notes |
|---|---|---|
| `REDIS_CLIENT` | `phpredis` | PHP extension (recommended) or `predis` |
| `REDIS_HOST` | `127.0.0.1` | Docker service `redis` in sail compose |
| `REDIS_PORT` | `6379` | Mapped to `6379` on host |
| `REDIS_PASSWORD` | `null` | Set for production Redis |
| `REDIS_DB` | `0` | Default Redis database |
| `REDIS_CACHE_DB` | `1` | Separate Redis database for cache |

Two pre-configured Redis connections:
- **`default`** — general-purpose (queue, session)
- **`cache`** — dedicated for cache store (separate DB index)

---

## 7. Session

**File**: `backend/config/session.php`

| Setting | Env | Default | Notes |
|---|---|---|---|
| `driver` | `SESSION_DRIVER` | `database` | Options: `file`, `cookie`, `database`, `redis`, `memcached`, `dynamodb`, `array` |
| `lifetime` | `SESSION_LIFETIME` | 120 (minutes) | Session idle expiry |
| `expire_on_close` | `SESSION_EXPIRE_ON_CLOSE` | `false` | `true` = session expires when browser closes |
| `encrypt` | `SESSION_ENCRYPT` | `false` | **Production**: set `true` to encrypt session data at rest |
| `connection` | `SESSION_CONNECTION` | — | DB connection for `database`/`redis` drivers |
| `table` | `SESSION_TABLE` | `sessions` | DB table name for `database` driver |
| `store` | `SESSION_STORE` | — | Cache store for `apc`/`dynamodb`/`memcached`/`redis` |
| `lottery` | — | `[2, 100]` | Session garbage collection probability (2%) |
| `cookie` | `SESSION_COOKIE` | `{APP_NAME}_session` | Cookie name (derived from app name) |
| `path` | `SESSION_PATH` | `/` | Cookie path |
| `domain` | `SESSION_DOMAIN` | `null` | Cross-subdomain cookie sharing |
| `secure` | `SESSION_SECURE_COOKIE` | — | `true` in production (HTTPS only) |
| `http_only` | `SESSION_HTTP_ONLY` | `true` | Prevent JavaScript cookie access |
| `same_site` | `SESSION_SAME_SITE` | `lax` | CSRF protection. Options: `lax`, `strict`, `none` |

---

## 8. Queue

**File**: `backend/config/queue.php`

### Default Connection
```php
'default' => env('QUEUE_CONNECTION', 'database'),
```

### Available Drivers

| Driver | Env | Notes |
|---|---|---|
| `sync` | — | Synchronous execution (no queue worker needed) |
| `database` | `QUEUE_CONNECTION=database` | Simple, no dependencies. Default |
| `redis` | `QUEUE_CONNECTION=redis` | **Production** — much faster, supports delays |
| `beanstalkd` | `QUEUE_CONNECTION=beanstalkd` | Requires Beanstalkd server |
| `sqs` | `QUEUE_CONNECTION=sqs` | AWS SQS |
| `null` | — | Discards jobs |

### Job Batching
- **Database**: defaults to primary DB connection
- **Table**: `job_batches`

### Failed Jobs
- **Driver**: `QUEUE_FAILED_DRIVER` (default: `database-uuids`)
- **Table**: `failed_jobs`

**Production recommendation**: `QUEUE_CONNECTION=redis`. Run `php artisan queue:work redis` (or use Laravel Horizon for monitoring).

---

## 9. Mail

**File**: `backend/config/mail.php`

### Default Mailer
```php
'default' => env('MAIL_MAILER', 'log'),
```

### Mailer Configurations

| Mailer | Transport | When to Use |
|---|---|---|
| `smtp` | SMTP server | Any SMTP provider (SendGrid, Mailtrap, etc.) |
| `log` | Laravel log | Development — writes to `storage/logs/laravel.log` |
| `array` | Array | Testing |
| `mailgun` | Mailgun API | Production — high-volume transactional email |
| `ses` | AWS SES | AWS-native deployments |
| `postmark` | Postmark API | Transactional email with fast delivery |
| `resend` | Resend API | Modern email API |
| `sendmail` | Sendmail binary | Legacy server setups |
| `failover` | SMTP → Log | Fallback chain |
| `roundrobin` | SES ↔ Postmark | Load balancing across providers |

### Common SMTP Settings

| Env Variable | Default | Notes |
|---|---|---|
| `MAIL_HOST` | `127.0.0.1` | SMTP server hostname |
| `MAIL_PORT` | `2525` | SMTP port (587 for TLS, 465 for SSL) |
| `MAIL_USERNAME` | `null` | SMTP username |
| `MAIL_PASSWORD` | `null` | SMTP password |
| `MAIL_FROM_ADDRESS` | `hello@example.com` | Global "from" address |
| `MAIL_FROM_NAME` | `{$APP_NAME}` | Global "from" name |

### Mailgun
```php
'mailgun' => [
    'domain' => env('MAILGUN_DOMAIN'),
    'secret' => env('MAILGUN_SECRET'),
    'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
]
```

---

## 10. Logging

**File**: `backend/config/logging.php`

### Default Channel
```php
'default' => env('LOG_CHANNEL', 'stack'),
```

### Channels

| Channel | Driver | Best For |
|---|---|---|
| `stack` | Multi-channel | Combines multiple channels. Default. |
| `single` | Single file | Simple logging to `storage/logs/laravel.log` |
| `daily` | Rotating files | Production (14-day retention by default) |
| `slack` | Slack webhook | Critical error alerts |
| `papertrail` | Syslog UDP | Centralised log aggregation |
| `stderr` | STDERR stream | Docker/container environments |
| `syslog` | System logger | Server environments |
| `errorlog` | PHP error log | Minimal setups |
| `null` | Discard | Tests |

### Log Levels
- **Env**: `LOG_LEVEL`
- **Default**: `debug`
- **Options** (ascending severity): `debug`, `info`, `notice`, `warning`, `error`, `critical`, `alert`, `emergency`
- **Production**: Set to `warning` or `error` to reduce disk I/O.

### Stack Channel Composition
- **Env**: `LOG_STACK`
- **Default**: `single`
- Change to `daily` to get rotating log files.

### Deprecations
- **Channel**: `LOG_DEPRECATIONS_CHANNEL` (default: `null` = silent)
- **Trace**: `LOG_DEPRECATIONS_TRACE` (default: `false`)
- Enable during upgrades to catch deprecated API usage.

---

## 11. Filesystem

**File**: `backend/config/filesystems.php`

### Default Disk
```php
'default' => env('FILESYSTEM_DISK', 'local'),
```

### Disks

| Disk | Driver | Root | Visibility | Use Case |
|---|---|---|---|---|
| `local` | local | `storage/app/private` | Private | Internal app files |
| `public` | local | `storage/app/public` | Public | User uploads, avatars |
| `s3` | s3 | — | Configurable | Cloud storage for production |

### S3 Configuration

| Env Variable | Default | Notes |
|---|---|---|
| `AWS_ACCESS_KEY_ID` | — | IAM access key |
| `AWS_SECRET_ACCESS_KEY` | — | IAM secret key |
| `AWS_DEFAULT_REGION` | `us-east-1` | Bucket region |
| `AWS_BUCKET` | — | Bucket name |
| `AWS_USE_PATH_STYLE_ENDPOINT` | `false` | Required for MinIO / DigitalOcean Spaces |
| `AWS_URL` | — | Custom endpoint URL |
| `AWS_ENDPOINT` | — | For S3-compatible services |

### Symbolic Links
- `public/storage → storage/app/public` (run `php artisan storage:link`)

---

## 12. Plugin System

LaravelCP has a first-party plugin system. Two config files govern it.

### `backend/config/plugins.php`

| Setting | Env | Default | Notes |
|---|---|---|---|
| `path` | — | `app/Plugins` | Directory where plugins live |
| `namespace` | — | `App\Plugins` | PHP namespace for plugin classes |
| `auto_discover` | — | `true` | Automatically register plugins on boot |
| `core_plugins` | — | `['Dashboard', 'Profile', 'Settings']` | Cannot be disabled |

#### Plugin Structure
```php
'structure' => [
    'routes'      => ['web.php', 'api.php', 'admin.php'],
    'controllers' => 'Controllers',
    'models'      => 'Models',
    'views'       => 'views',
    'migrations'  => 'database/migrations',
    'seeders'     => 'database/seeders',
    'hooks'       => 'hooks.php',
    'config'      => 'config.php',
    'assets'      => 'assets',
    'lang'        => 'lang',
],
```

#### Middleware per Route Type

| Route Type | Middleware |
|---|---|
| `web` | `web`, `auth` |
| `api` | `api`, `auth:sanctum` |
| `admin` | `web`, `auth`, `admin` |

#### Plugin Cache
| Env Variable | Default | Notes |
|---|---|---|
| `PLUGIN_CACHE_ENABLED` | `true` | Cache plugin discovery metadata |
| Cache key | `plugins.cache` | — |
| Cache TTL | 3600 seconds (1 hour) | Increase for production |

### `backend/config/plugin_schema.php`

Defines the validation contract for every plugin's `plugin.json` manifest.

#### Required Fields
`name`, `slug`, `version`, `author`, `description`

#### Optional Fields
`requires`, `settings`, `hooks`, `routes`, `permissions`, `frontend`, `license_required`, `admin_settings`

#### Frontend Contract
- **routes**: path, name, component, meta
- **slots**: dashboard-widget, sidebar-panel, profile-tab, inventory-slot, navigation-item, header-slot, user-menu-item
- **dashboard_widgets**: name, component, width (full/half/third/quarter), order, props

#### Admin Settings Schema
Plugins can register their own settings sections:
```json
{
  "admin_settings": {
    "combat": {
      "label": "Combat",
      "icon": "FireIcon",
      "order": 10,
      "settings": {
        "attack_cooldown": {
          "type": "number",
          "label": "Attack Cooldown (seconds)",
          "default": 300,
          "description": "Cooldown between attacks",
          "min": 0,
          "max": 3600
        }
      }
    }
  }
}
```
Supported field types: `text`, `number`, `boolean`, `select`, `json`.

---

## 13. OAuth / Services

**File**: `backend/config/services.php`

### OAuth Providers

All five providers share the same credential pattern:

| Provider | Env Prefix | Default |
|---|---|---|
| Discord | `DISCORD_` | — |
| Google | `GOOGLE_` | — |
| GitHub | `GITHUB_` | — |
| Twitter/X | `TWITTER_` | — |
| Facebook | `FACEBOOK_` | — |

Each requires:
- `{PROVIDER}_CLIENT_ID`
- `{PROVIDER}_CLIENT_SECRET`
- `{PROVIDER}_REDIRECT_URI` (e.g. `https://your-domain.com/auth/{provider}/callback`)

### Transactional Email Services

| Service | Env Variables | Notes |
|---|---|---|
| Mailgun | `MAILGUN_DOMAIN`, `MAILGUN_SECRET`, `MAILGUN_ENDPOINT` | Used when `MAIL_MAILER=mailgun` |
| Postmark | `POSTMARK_TOKEN` | Used when `MAIL_MAILER=postmark` |
| SES | `AWS_ACCESS_KEY_ID`, `AWS_SECRET_ACCESS_KEY`, `AWS_DEFAULT_REGION` | Used when `MAIL_MAILER=ses` |
| Resend | `RESEND_KEY` | Used when `MAIL_MAILER=resend` |

### Slack Notifications
```php
'slack' => [
    'notifications' => [
        'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
        'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
    ],
]
```

---

## 14. Permissions (Spatie)

**File**: `backend/config/permission.php`

### Core Models
| Model | Class |
|---|---|
| Permission | `Spatie\Permission\Models\Permission` |
| Role | `Spatie\Permission\Models\Role` |

### Database Tables

| Table | Purpose |
|---|---|
| `roles` | Role definitions |
| `permissions` | Permission definitions |
| `model_has_permissions` | User-permission assignment |
| `model_has_roles` | User-role assignment |
| `role_has_permissions` | Role-permission assignment |

### Features

| Setting | Default | Notes |
|---|---|---|
| `teams` | `false` | Enable for multi-team permission system |
| `register_permission_check_method` | `true` | Registers Gate-based permission checks |
| `events_enabled` | `false` | Fire events on role/permission changes |
| `display_permission_in_exception` | `false` | Show permission names in 403 errors |
| `display_role_in_exception` | `false` | Show role names in 403 errors |
| `enable_wildcard_permission` | `false` | Enable `*` wildcard matching |

### Permission Cache
| Setting | Default | Notes |
|---|---|---|
| `expiration_time` | 24 hours | How long permissions are cached |
| `key` | `spatie.permission.cache` | Cache key |
| `store` | `default` | Uses `cache.php` default store |

**After modifying permissions/roles, run**: `php artisan permission:cache-reset`

---

## 15. Scribe (API Docs)

**File**: `backend/config/scribe.php`

Scribe auto-generates interactive API documentation from your route annotations. It is a dev-only dependency.

| Setting | Value / Default | Notes |
|---|---|---|
| `title` | `{APP_NAME} API Documentation` | HTML `<title>` |
| `type` | `laravel` | Blade view (vs. static HTML) |
| `base_url` | `{APP_URL}` | Displayed as the API base URL |
| `auth.enabled` | `true` | API auth section shown in docs |
| `auth.default` | `true` | All endpoints assumed authenticated by default |
| `auth.in` | `bearer` | Token location |
| `auth.name` | `Authorization` | Header name |
| `auth.placeholder` | `YOUR_ACCESS_TOKEN` | Example placeholder |
| `try_it_out.enabled` | `true` | Interactive API tester |
| `try_it_out.use_csrf` | `true` | CSRF token for Sanctum SPA testing |
| `try_it_out.csrf_url` | `/sanctum/csrf-cookie` | CSRF cookie endpoint |
| `postman.enabled` | `true` | Generates Postman collection |
| `openapi.enabled` | `true` | Generates OpenAPI 3.0.3 spec |
| `example_languages` | bash, javascript, php | Code sample languages |

### Regenerate Docs
```bash
php artisan scribe:generate
```
The generated docs are available at `/docs` when `laravel.add_routes` is `true`. Postman collection at `/docs.postman`, OpenAPI spec at `/docs.openapi`.

---

## 16. Emojis

**File**: `backend/config/emojis.php`

Pre-defined emoji categories for the chat system:

| Category | Count (approx.) | Usage |
|---|---|---|
| `smileys` | 30 | Facial expressions |
| `gestures` | 28 | Hand gestures, body parts |
| `emotions` | 30 | Hearts, symbols |
| `animals` | 30 | Animals, nature |
| `food` | 30 | Food & drink |
| `activities` | 30 | Sports, activities |
| `symbols` | 30 | Stars, badges, icons |

### Quick Reactions
```php
'quick_reactions' => ['👍', '❤️', '😂', '😮', '😢', '🎉', '🔥', '👀'],
```
Displayed as one-tap reaction buttons in chat.

### Validation Pattern
A Unicode regex covering emoji ranges from U+1F600 through U+1FAFF plus common symbol ranges (U+2600–U+27BF). Use this for server-side emoji validation.

---

## 17. Docker Services

### Root `docker-compose.yml`

Primary development stack. Four services:

#### Frontend Service
| Property | Value |
|---|---|
| Container | `laravelcp_frontend` |
| Exposed port | `5175` (host) → `5175` (container) |
| Environment | `NODE_ENV=development` |
| Command | `npm run dev -- --host 0.0.0.0` |
| Volume | `./frontend:/app` (live code sync) |

#### Backend Service
| Property | Value |
|---|---|
| Container | `laravelcp_backend` |
| Exposed port | `8001` (host) → `80` (container) |
| Environment | `APACHE_DOCUMENT_ROOT=/var/www/html/public`, `APP_DEBUG=1` |
| Restart policy | `unless-stopped` |

**VERIFY**: The backend Dockerfile uses Apache with `.htaccess` support. The document root must point to `public/`.

#### MySQL Service
| Property | Value |
|---|---|
| Image | `mysql:8.0` |
| Container | `laravelcp_db` |
| Exposed port | `3307` (host) → `3306` (container) |
| Root password | `root` |
| Database | `laravelcp` |
| User/Pass | `laravelcp` / `laravelcp` |
| Volume | `mysql_data` (persistent) |
| Healthcheck | `mysqladmin ping` every 5s |

#### phpMyAdmin Service
| Property | Value |
|---|---|
| Container | `laravelcp_pma` |
| Exposed port | `8082` (host) → `80` (container) |
| PMA_HOST | `mysql` |
| User/Pass | `root` / `root` |

#### Network & Volumes
- Network: `laravelcp_net` (bridge driver)
- Volume: `mysql_data` (persists database across restarts)

#### Start the Stack
```bash
docker compose up -d
```

### Sail `backend/docker-compose.yml`

Laravel Sail-based stack with additional services:

| Service | Container | Purpose |
|---|---|---|
| `app` | `laravel_app` | Apache/PHP-FPM backend (port 8001, Vite 5174, WS 6002) |
| `db` | `laravel_db` | MySQL 8.0 (port 3307) |
| `redis` | — | Redis Alpine (port 6379) |
| `scheduler` | `laravel_scheduler` | Artisan schedule runner (every 60s) |

Environment variables use `${VAR:-default}` interpolation — values fall back to defaults if not set.

**VERIFY**: The Sail compose file references a database named `peptide_community` in its defaults, which may diverge from the root compose file's `laravelcp` database name. Ensure your `.env` values match the intended stack.

---

## 18. Frontend (Vite / Vue 3)

### `frontend/vite.config.ts`

#### Server
| Setting | Value | Notes |
|---|---|---|
| `server.port` | `5175` | Matches docker-compose mapping |
| `server.host` | `true` | Listen on all interfaces (for Docker) |
| `server.strictPort` | `true` | Fail if port is taken |
| `server.allowedHosts` | Array of `.orb.local` domains | Laravel Herd / OrbStack support |
| `server.hmr.clientPort` | `5175` | WebSocket port for HMR |

#### Proxy
```
/api → http://host.docker.internal:8001
```
- `changeOrigin: true`
- **VERIFY**: `host.docker.internal` is Docker's DNS for the host machine. If the backend runs on the host, this works. If the backend is inside Docker, use the container network name (`backend`).

#### Build
| Setting | Value | Notes |
|---|---|---|
| `outDir` | `dist` | Build output |
| `sourcemap` | `false` | Disable in production |
| `minify` | `esbuild` | Fast minifier |
| `cssCodeSplit` | `true` | Per-component CSS files |
| `chunkSizeWarningLimit` | 500 KB | Warning threshold |

#### Chunk Splitting
| Chunk Name | Contents |
|---|---|
| `vue-vendor` | Vue, Vue Router, Pinia |
| `api-vendor` | Axios |
| `vueuse-vendor` | @vueuse utilities |
| `views-admin` | Admin view pages |
| `views-modules` | Module view pages |
| `views-plugins` | Plugin view pages |
| `views-core` | Root-level views |
| `stores` | Pinia stores |
| `components` | Shared components |
| `services` | Services & composables |

#### Plugins
- `@vitejs/plugin-vue` — Vue SFC compilation
- `@vitejs/plugin-vue-jsx` — JSX/TSX support
- `vite-plugin-compression` — Gzip + Brotli pre-compression (>1KB files)
- `rollup-plugin-visualizer` — Bundle analysis at `dist/stats.html`

#### Optimizations
| Setting | Value | Notes |
|---|---|---|
| `optimizeDeps.include` | vue, vue-router, pinia, axios | Pre-bundle these |
| `esbuild.drop` | `['console', 'debugger']` | Removed in production builds |

### `frontend/tsconfig` Structure

| File | Extends | Purpose |
|---|---|---|
| `tsconfig.json` | — | Root references only |
| `tsconfig.app.json` | `@vue/tsconfig/tsconfig.dom.json` | App source code config |
| `tsconfig.node.json` | — | Vite/Node config |
| `tsconfig.vitest.json` | — | Test runner config |
| `e2e/tsconfig.json` | — | E2E test config |

### Key tsconfig.app.json Settings
| Setting | Value |
|---|---|
| `moduleResolution` | `bundler` |
| `strict` | `true` |
| `noEmit` | `true` |
| `resolveJsonModule` | `true` |
| `isolatedModules` | `true` |
| `skipLibCheck` | `true` |
| Path alias | `@/*` → `./src/*` |

### Frontend Environment (`frontend/.env`)
| Variable | Default | Notes |
|---|---|---|
| `VITE_API_URL` | `/api` | Backend API URL. For dev proxied through Vite; for production set to absolute URL or keep relative if same-domain |

Vite requires the `VITE_` prefix. Only `VITE_*` variables are exposed to the client bundle. The backend `VITE_APP_NAME` in `backend/.env` is used during backend rendering (Inertia/Breeze) — not by the frontend Vite build directly.

---

## 19. License Configuration

LaravelCP uses a license activation system. These variables are **required** for the application to function.

### `LARAVEL_CP_LICENSE`
- **Format**: License key string
- **Purpose**: Validates that you have a legitimate copy of LaravelCP
- **Where to get**: Provided upon purchase

### `LCP_LICENSE_PRIVATE_KEY`
- **Format**: PEM-encoded RSA private key as a single-line or multi-line string
- **Purpose**: Decrypts license validation payloads from the license server
- **Notes**: Mutually exclusive with `LCP_LICENSE_PRIVATE_KEY_PATH`

### `LCP_LICENSE_PRIVATE_KEY_PATH`
- **Format**: Absolute filesystem path to the PEM private key file
- **Purpose**: Alternative to inline key — reads key from a file
- **Security**: Do NOT store the key file inside a web-accessible directory

### `LICENSE_CALLBACK_SECRET`
- **Format**: Random string (32+ characters recommended)
- **Purpose**: HMAC secret for license activation callback verification
- **Security**: **Must be set to a strong random value in production**

### Verification Flow
1. On boot/install, the application sends a license verification request
2. The license server responds with an encrypted payload
3. The private key decrypts the response
4. `LICENSE_CALLBACK_SECRET` validates the HMAC signature to prevent tampering

**VERIFY**: License validation is triggered during the web installer at `/install` and on application boot. If any of these four values are missing or invalid, the application may fail to start or show a license error page.

---

## 20. Environment-Specific Recommendations

### Local Development

| Setting | Value | Reason |
|---|---|---|
| `APP_ENV` | `local` | Verbose error pages, no config caching |
| `APP_DEBUG` | `true` | Full stack traces |
| `QUEUE_CONNECTION` | `database` | Zero dependencies |
| `CACHE_STORE` | `database` | Zero dependencies |
| `SESSION_ENCRYPT` | `false` | Slightly faster, not needed locally |
| `MAIL_MAILER` | `log` | View emails in `storage/logs/laravel.log` |
| `CORS_ALLOWED_ORIGINS` | `http://localhost:5175` | Frontend dev server origin |
| `SANCTUM_STATEFUL_DOMAINS` | `localhost,localhost:5175` | SPA cookie auth |

### Production

| Setting | Value | Reason |
|---|---|---|
| `APP_ENV` | `production` | Config caching, minimal overhead |
| `APP_DEBUG` | `false` | Never expose internals |
| `APP_URL` | `https://your-domain.com` | Canonical production URL |
| `FRONTEND_URL` | `https://your-domain.com` | Production frontend URL |
| `QUEUE_CONNECTION` | `redis` | Performance + job persistence |
| `CACHE_STORE` | `redis` | Avoid DB contention under load |
| `SESSION_DRIVER` | `redis` (or `database`) | Redis is faster |
| `SESSION_ENCRYPT` | `true` | Encrypt session data at rest |
| `MAIL_MAILER` | `smtp` or `mailgun` | Actually sends email |
| `LOG_LEVEL` | `warning` | Reduce log verbosity |
| `CORS_ALLOWED_ORIGINS` | `https://your-domain.com` | Specific, not wildcard |
| `CORS_SUPPORTS_CREDENTIALS` | `true` | SPA cookie auth |
| `SANCTUM_STATEFUL_DOMAINS` | `your-domain.com` | Production SPA origin |
| `SESSION_SECURE_COOKIE` | `true` | HTTPS-only cookies |
| `SESSION_SAME_SITE` | `lax` | CSRF protection |
| `APP_KEY` | Random 32-char base64 | Must be generated, never committed |
| `LICENSE_CALLBACK_SECRET` | Random 32+ chars | Tamper-proof callbacks |

### Production Checklist

- [ ] `APP_DEBUG=false`
- [ ] `APP_KEY` generated (`php artisan key:generate`)
- [ ] `LICENSE_CALLBACK_SECRET` set to a strong random value
- [ ] `LARAVEL_CP_LICENSE` set to your purchased license key
- [ ] `LCP_LICENSE_PRIVATE_KEY` or `LCP_LICENSE_PRIVATE_KEY_PATH` configured
- [ ] `QUEUE_CONNECTION=redis` with Redis running
- [ ] `CACHE_STORE=redis`
- [ ] `MAIL_MAILER=smtp` (or `mailgun`) with valid credentials
- [ ] `SESSION_ENCRYPT=true`
- [ ] `SESSION_SECURE_COOKIE=true`
- [ ] CORS origins restricted to your actual frontend domain
- [ ] Sanctum stateful domains set to production frontend domain
- [ ] `php artisan config:cache`
- [ ] `php artisan route:cache`
- [ ] `php artisan event:cache`
- [ ] `php artisan optimize`
- [ ] `npm run build` (frontend)
- [ ] Database credentials use a strong, unique password
- [ ] SSL/TLS enabled on the web server
