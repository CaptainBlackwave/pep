# LaravelCP Architecture

> **LaravelCP** is a Persistent Browser-Based Game (PBBG) platform with a modular
> plugin ecosystem, built on Laravel 11 + Vue 3 + TypeScript.

---

## Table of Contents

1. [Technology Stack](#1-technology-stack)
2. [Directory Structure](#2-directory-structure)
3. [Core System Architecture](#3-core-system-architecture)
4. [Service Layer](#4-service-layer)
5. [Plugin System](#5-plugin-system)
6. [License System](#6-license-system)
7. [API Architecture](#7-api-architecture)
8. [Data Flow](#8-data-flow)
9. [Database Schema](#9-database-schema)
10. [Frontend Architecture](#10-frontend-architecture)
11. [Security](#11-security)

---

## 1. Technology Stack

| Layer        | Technology                              |
|-------------|-----------------------------------------|
| Backend     | PHP 8.3, Laravel 11, Apache             |
| Frontend    | Vue 3, TypeScript, Vite                 |
| Database    | MySQL 8.0                               |
| Auth        | Laravel Sanctum, Socialite (OAuth)      |
| 2FA         | pragmarx/google2fa + simple-qrcode       |
| WebSockets  | Laravel Reverb (with polling fallback)  |
| Permissions | Spatie laravel-permission               |
| Docker      | php:8.3-apache, node:20-alpine, mysql:8 |
| Testing     | PHPUnit 11, Vitest, Playwright          |

**Key dependencies** (`backend/composer.json`):

- `laravel/reverb` — WebSocket server
- `laravel/sanctum` — API token auth
- `laravel/socialite` — OAuth providers
- `spatie/laravel-permission` — RBAC
- `pragmarx/google2fa` — Two-factor auth
- `socialiteproviders/discord` — Discord OAuth
- `golonka/bbcodeparser` — BBCode parsing
- `joypixels/emoji-toolkit` — Emoji rendering
- `knuckleswtf/scribe` — API documentation

---

## 2. Directory Structure

```
forum/
  docker-compose.yml              # Full dev environment (frontend + backend + mysql + phpmyadmin)
  backend/
    Dockerfile                    # php:8.3-apache with Node 20
    docker-compose.yml            # Standalone backend services
    docker-entrypoint.sh
    start-server.sh
    app/
      Core/                       # All core application code
      Plugins/                    # Plugin system directory
        Plugin.php                #   Base plugin class
        testplugin/               #   Example plugin
          plugin.json
          TestPluginPlugin.php
          hooks.php
          routes/
      Console/Commands/
      Facades/
      Mail/
    bootstrap/
    config/                       # Laravel config + plugin_schema.php, plugins.php
    database/
      migrations/                 # 42 migration files
      seeders/                    # 24 seeder files
      factories/
    resources/
    routes/
      api.php                     # All API route definitions (v1)
      admin.php                   # Legacy admin sidebar route
      web.php
      console.php
    storage/
    tests/
      Feature/
      Unit/
    themes/
    vendor/
  frontend/
    Dockerfile                    # node:20-alpine
    src/
      main.ts                     # App entry point
      App.vue
      assets/
      components/
        layout/                   # CoreLayout.vue, Sidebar, Header, etc.
        ui/                       # Reusable UI components
        icons/
        peptide/                  # PBBG-specific components
      composables/
        useLazyLoad.ts
        usePluginRoutes.ts
        useServiceWorker.ts
        useToast.ts
        useWebSocket.ts
      config/
        env.ts
      layouts/
        CoreLayout.vue
      plugins/
        errorLogger.ts
      router/
        index.ts                  # Vue Router with dynamic plugin routes
      services/
        api.ts                    # Axios-based API client (595 lines)
        websocket.ts              # Reverb WebSocket client (488 lines)
        plugin-bus.ts             # Frontend plugin hook system (239 lines)
        plugin-loader.ts          # Dynamic plugin module loader (225 lines)
      stores/
        auth.ts                   # Pinia auth store
        hub.ts                    # Plugin hub/registry store
        plugins.ts                # Plugin management store
        notifications.ts
        user.ts
      types/
        api.ts
        notification.ts
        plugin-route.ts
        plugin.ts
        router.ts
        user.ts
        websocket.ts
      views/
        admin/                    # Admin panel views
        HomeView.vue
        LoginView.vue
        RegisterView.vue
        PeptideCommunityView.vue  # Main game view
        ...
    e2e/                          # Playwright E2E tests
    vitest.config.ts
    vite.config.ts
```

---

## 3. Core System Architecture

All core application code lives under `backend/app/Core/`:

```
app/Core/
  Admin/                # Admin sidebar & menu schema
  Console/              # Artisan commands (InstallCommand)
  Contracts/            # Plugin & game system interfaces
  Events/               # WebSocket broadcast + system events
  Exceptions/           # Custom exception classes
  Facades/              # Economy, Combat, Inventory, TextFormatter facades
  Helpers/              # Global helper functions (settings, broadcast)
  Http/
    Controllers/        # All controller classes
    Kernel.php
    Middleware/         # InstallerLocked middleware
    Requests/           # Form request validation
    Resources/          # API resource transformers
  Lifecycle/            # Plugin lifecycle abstraction
  Middleware/           # Additional middleware
  Models/               # 41 Eloquent models
  Pipeline/             # Action pipeline (pipes & context)
  Policies/             # Authorization policies
  Providers/            # Service providers
  Services/             # 28 service classes
  Traits/               # Reusable model traits
```

### 3.1 Controllers

**Auth** — `app/Core/Http/Controllers/Auth/`

| Controller                 | Endpoints                                                |
|---------------------------|----------------------------------------------------------|
| OAuthController           | OAuth redirect, callback, link/unlink, list providers    |
| TwoFactorAuthController   | 2FA setup, confirm, disable, recovery codes, verify      |
| PasswordResetController   | Send reset link, validate token, reset password          |

**API** — `app/Core/Http/Controllers/Api/`

| Controller             | Function                            |
|------------------------|-------------------------------------|
| MarketplaceController  | Plugin marketplace integration      |
| PluginRegistryController | Remote plugin registry queries    |

**Admin** — `app/Core/Http/Controllers/Admin/` (37 controllers)

| Category              | Controllers                                                                 |
|-----------------------|-----------------------------------------------------------------------------|
| Dashboard             | DashboardStatsController                                                    |
| Users                 | UserManagementController, UserToolsController                               |
| Roles & Permissions   | RolePermissionController                                                    |
| Plugins               | PluginController (in general controllers)                                   |
| Settings              | SettingsController                                                          |
| Webhooks              | WebhookController                                                           |
| Email                 | EmailSettingsController                                                     |
| Cache                 | CacheController                                                             |
| Logs                  | ErrorLogController, ActivityLogController                                   |
| Backups               | BackupController                                                            |
| System                | SystemHealthController, SystemController                                    |
| License               | LicenseController                                                           |
| Moderation            | ModerationController, IpBanController                                       |
| Staff Chat            | StaffChatController                                                         |
| Developer             | DeveloperController                                                         |
| Type Tables (16)      | ItemRarity, PropertyType, AnnouncementType, CrimeDifficulty, CasinoGameType, CompanyIndustry, StockSector, CourseSkill, CourseDifficulty, AchievementStat, MissionFrequency, MissionObjectiveType, BountyStatus, LotteryStatus, ItemEffectType, ItemModifierType |

**General** — `app/Core/Http/Controllers/`

| Controller                    | Endpoints                                              |
|------------------------------|--------------------------------------------------------|
| AuthController               | `register`, `login`, `logout`, `logoutAll`, `user`, `changePassword`, `updateUsername` |
| DashboardController          | `dashboard`                                            |
| ProfileController            | `player/{id}`                                          |
| PlayerStatsController        | `stats/`, `stats/player/{userId}`, `stats/refresh`     |
| ActivityController           | `activity/`, `activity/my-activity`                    |
| NotificationController       | CRUD for user notifications                            |
| PluginController             | Plugin CRUD, install, enable, disable, themes          |
| CommunityDiscussionController| Public & authenticated community discussions/replies   |
| EmojiController              | Emoji lists, search, quick-reactions                   |
| TextFormatterController      | BBCode preview, emoji search, plain text conversion    |
| WebSocketController          | Channel auth, polling, online count, heartbeat, presence |
| FrontendErrorController      | Client-side error logging                              |
| InstallerController          | First-run installation wizard                          |

### 3.2 Middleware

All middleware is loaded via `Kernel.php`. Available middleware:

| Middleware         | File                                       | Purpose                                  |
|--------------------|-------------------------------------------|------------------------------------------|
| `verify.license`   | (defined in `AppServiceProvider` or plugin)| Ensures valid license for admin routes  |
| `auth:sanctum`     | Laravel Sanctum                            | API token / session auth                |
| `role:admin\|moderator` | Spatie                               | Role-based access control               |
| `throttle:X,Y`     | Laravel built-in                           | Rate limiting                           |
| `InstallerLocked`  | `app/Core/Http/Middleware/InstallerLocked.php` | Blocks installer after setup       |

The middleware chain for admin routes is:
```
auth:sanctum → role:admin|moderator → verify.license
```

### 3.3 Contracts (Interfaces)

All in `app/Core/Contracts/`:

| Interface                | Purpose                                               |
|--------------------------|-------------------------------------------------------|
| `PluginInterface`        | Plugin identity, manifest, routes, permissions, slots |
| `PluginLifecycleInterface` | `install()`, `enable()`, `disable()`, `uninstall()`, `upgrade()` |
| `EconomyInterface`       | Money operations (credit, debit, transfer, balance)   |
| `CombatInterface`        | Combat system contract (PVP, NPC)                     |
| `InventoryInterface`     | Item management (add, remove, equip, use)             |

### 3.4 Pipeline (Action Pipeline)

Located in `app/Core/Pipeline/`:

```
ActionContext               # Value object: player, action, payload, result, errors
ActionPipeline              # Pipeline orchestrator: runs holes through ordered pipes
Pipes/
  ValidateActionPipe        # Validate input data
  RateLimitPipe             # Enforce action rate limits
  BeforeHooksPipe           # Fire before-hooks for plugin interception
  ExecutePipe               # Execute core action logic
  CooldownPipe              # Set action cooldowns
  AfterHooksPipe            # Fire after-hooks for plugin reactions
  LogActionPipe             # Log the action to activity log
```

Pipeline order (defined in `ActionPipeline.php`):
```
ValidateActionPipe → [extraPipes] → RateLimitPipe → BeforeHooksPipe →
ExecutePipe → CooldownPipe → AfterHooksPipe → LogActionPipe
```

Usage example:
```php
$context = new ActionContext($player, 'crime.commit', ['crime_id' => $crime->id], 'crimes');
$result  = app(ActionPipeline::class)->run($context, function (ActionContext $ctx) use ($crime) {
    $ctx->result = $this->crimeService->attemptCrime($ctx->player, $crime);
});
```

### 3.5 Events

Located in `app/Core/Events/`:

| Event                    | Purpose                                    |
|--------------------------|--------------------------------------------|
| `WebSocketBroadcast`     | Generic WebSocket event (ShouldBroadcast)  |
| `PluginBroadcastEvent`   | Plugin-specific WebSocket broadcast        |
| `Economy/MoneyCredited`  | Economy credit event                       |
| `Economy/MoneyDebited`   | Economy debit event                        |
| `Economy/MoneyTransferred` | Economy transfer event                   |
| `Module/ModuleHookEvent` | Generic module hook event                  |
| `Module/OnCombat`        | Combat event                               |
| `Module/OnCrimeCommit`   | Crime event                                |
| `Module/OnLevelUp`       | Level-up event                             |
| `Module/OnPlayerLogin`   | Player login event                         |
| `Module/OnPurchase`      | Purchase event                             |
| `Module/OnTravel`        | Travel event                               |

The `WebSocketBroadcast` event auto-determines channel type:
- `user.*`, `combat.*`, `admin` → `PrivateChannel`
- `gang.*` → `PresenceChannel`
- Everything else → public `Channel`

### 3.6 Providers

| Provider                     | Responsibility                                           |
|------------------------------|----------------------------------------------------------|
| `AppServiceProvider`         | Register `text-formatter`, `economy` singletons, rate limiters, hook definitions, policies |
| `PluginServiceProvider`      | Discover & load plugins, register plugin routes         |
| `HookServiceProvider`        | Register hook system services                           |
| `AutoPluginHookLoader`       | Auto-load plugin hooks.php files                        |

---

## 4. Service Layer

All 28 services in `app/Core/Services/`:

| # | Service | Description |
|---|---------|-------------|
| 1 | `ActivityLogService` | Records player & system activity with context |
| 2 | `AdminNotificationService` | Creates/manages admin-facing notifications |
| 3 | `CacheService` | Centralized cache operations with tags & invalidation |
| 4 | `DiscordWebhookService` | Sends messages to Discord channels via webhooks |
| 5 | `GameHooks` | Static hook listener registry `listen()` / `apply()` |
| 6 | `HookRegistry` | Formal hook schema definitions with payload validation |
| 7 | `HookService` | Instance-based hook manager with priority sorting & execution |
| 8 | `LaravelLogReader` | Reads/parses `storage/logs/laravel.log` for admin UI |
| 9 | `LicenseService` | RSA-256 license key generation, verification, activation |
| 10 | `MarketplaceClient` | HTTP client for plugin marketplace API |
| 11 | `MetricsRegistry` | Collects & exposes runtime metrics (hook counts, etc.) |
| 12 | `ModerationService` | Player bans, IP bans, warnings, flag management |
| 13 | `NotificationService` | User notification creation, delivery, preferences |
| 14 | `OAuthService` | OAuth provider management, account linking |
| 15 | `PluginBundleService` | Bundles multiple plugins for installation |
| 16 | `PluginContext` | Runtime context for active plugin execution |
| 17 | `PluginManagerService` | High-level plugin CRUD (install, uninstall, enable, disable) |
| 18 | `PluginManifestService` | Parses & validates plugin.json manifests |
| 19 | `PluginRegistry` | Local plugin registry (discovered plugins cache) |
| 20 | `PluginService` | Low-level plugin operations, hook registration |
| 21 | `SemverResolver` | Semantic version constraint resolution for dependencies |
| 22 | `SettingService` | Key-value settings with plugin-prefixed keys, defaults |
| 23 | `SlackWebhookService` | Sends messages to Slack channels via webhooks |
| 24 | `TextFormatterService` | BBCode parsing, emoji rendering, text sanitization |
| 25 | `TimerService` | Cooldown & action timers (per-player, per-action) |
| 26 | `TwoFactorAuthService` | Google Authenticator TOTP setup & verification |
| 27 | `WebhookService` | Outgoing webhook delivery with retry & signing |
| 28 | `WebSocketService` | Channel management, broadcasting, presence, polling |

### Facades

| Facade    | Service Class      | Alias     |
|-----------|-------------------|-----------|
| `Economy` | `WalletService`   | `economy` |
| `Combat`  | (plugin-provided) | `combat`  |
| `Inventory` | (plugin-provided) | `inventory` |
| `TextFormatter` | `TextFormatterService` | `text-formatter` |

---

## 5. Plugin System

### 5.1 Architecture

```
Plugins/Plugin.php              # Abstract base class (implements both interfaces)
Core/Contracts/
  PluginInterface.php           # 14 methods: getId, getName, getRoutes, getPermissions, etc.
  PluginLifecycleInterface.php  # 5 lifecycle methods: install, enable, disable, uninstall, upgrade
Core/Services/
  PluginService.php             # Low-level plugin operations
  PluginManagerService.php      # High-level CRUD
  PluginManifestService.php     # Manifest parsing & validation
  PluginRegistry.php            # In-memory registry of discovered plugins
  PluginContext.php              # Runtime execution context
  PluginBundleService.php       # Multi-plugin bundles
  SemverResolver.php            # Dependency resolution
Core/Providers/
  PluginServiceProvider.php     # Discovers & registers plugins
  AutoPluginHookLoader.php      # Loads hooks.php files
Core/Traits/
  HasPluginMetadata.php         # Model trait for plugin metadata
```

### 5.2 Plugin Directory Structure

Each plugin lives in `backend/app/Plugins/{slug}/`:

```
{slug}/
  plugin.json               # Manifest (required: name, slug, version)
  {Slug}Plugin.php          # Plugin class extending App\Plugins\Plugin
  hooks.php                 # Hook registrations (auto-loaded)
  routes/
    web.php                 # Web routes
    api.php                 # API routes
    admin.php               # Admin routes
  Controllers/
  Models/
  views/
  database/
    migrations/
    seeders/
  assets/
  lang/
  config.php
  services.json             # Service container bindings (optional)
```

### 5.3 plugin.json Manifest

```json
{
    "name": "MyPlugin",
    "slug": "myplugin",
    "version": "1.0.0",
    "description": "...",
    "author": "...",
    "enabled": false,
    "license_required": false,
    "requires": {
        "laravel": "^11.0",
        "plugins": { "rpg-core": "^2.0" }
    },
    "settings": {
        "icon": "🎮",
        "color": "#6366f1",
        "menu": { "enabled": true, "order": 99, "section": "actions" },
        "route": "myplugin.home"
    },
    "permissions": {
        "myplugin.view": "View content",
        "myplugin.use": "Use features",
        "myplugin.admin": "Administer"
    },
    "hooks": { "after.crime.commit": "onCrimeCommit" },
    "routes": { "web": true, "api": true, "admin": false },
    "frontend": {
        "slots": {
            "dashboard-widget": ["GoldWidget.vue", "StatsWidget.vue"],
            "header-link": ["RpgNav.vue"]
        }
    },
    "admin_settings": {
        "combat": {
            "label": "Combat",
            "icon": "FireIcon",
            "order": 10,
            "settings": {
                "attack_cooldown": {
                    "type": "number",
                    "label": "Attack Cooldown",
                    "default": 300
                }
            }
        }
    },
    "middleware": ["throttle:game-actions"]
}
```

### 5.4 Plugin Lifecycle

```
Install   → migrations run → install() called → marked as installed
Enable    → enable() called → routes registered → hooks active
Disable   → disable() called → routes removed → hooks paused
Uninstall → uninstall() called → migrations rolled back → cleaned up
Upgrade   → upgrade($from, $to) called → data migrations run
```

The `PluginServiceProvider` discovers plugins at boot time, validates their
manifests, registers their routes (web/api/admin), loads their hooks, and
manages their enabled/disabled state.

### 5.5 Hook System

Three layers of hook infrastructure:

1. **`GameHooks`** — Static, simple `listen()` / `apply()` pattern. Used by plugin
   `hooks.php` files for lightweight hooking.
2. **`HookService`** — Instance-based with priority sorting, execution counts,
   and result collection. Used internally by services.
3. **`HookRegistry`** — Schema-based hook definitions with payload validation and
   stability tracking. All hooks are defined in `defineCoreHooks()`.

#### 50+ Defined Hooks

**Navigation:**
- `admin.sidebar` — Modify admin sidebar sections
- `customMenus` — Add custom navigation items
- `moduleLoad`, `alterModuleData` — Module lifecycle

**Economy:**
- `economy.credit`, `economy.debit` — Money flow interception

**Crimes:**
- `afterCrimeAttempt`, `modifyCrimeSuccessRate`, `alterCrimeData`, `OnCrimeCommit`

**Combat:**
- `afterCombat`, `modifyCombatPower`, `alterCombatTarget`, `OnCombat`

**Employment:**
- `OnJobApplied`, `OnWorkCompleted`, `OnJobQuit`, `alterEmploymentCompanies`

**Inventory:**
- `OnItemBought`, `OnItemSold`, `OnItemEquipped`, `OnItemUnequipped`, `OnItemUsed`
- `alterInventoryItemData`, `alterInventoryValue`, `alterEquipmentBonuses`

**Bank:**
- `OnBankDeposit`, `OnBankWithdraw`, `OnBankTransfer`
- `afterBankDeposit`, `afterBankWithdraw`, `afterBankTransfer`

**Generic Lifecycle:**
- `OnLevelUp`, `OnPlayerLogin`, `OnPurchase`, `OnTravel`

**Admin Dashboard:**
- `admin.dashboard.widgets` — Append stats widgets

**Action Pipeline Hooks** (before/after pairs with schema validation):
- `before.crime.commit` / `after.crime.commit`
- `before.combat.attack` / `after.combat.attack`
- `before.combat.hunt` / `after.combat.hunt`
- `before.combat.attack_npc` / `after.combat.attack_npc`
- `before.combat.auto_attack_npc` / `after.combat.auto_attack_npc`
- `before.bank.deposit` / `after.bank.deposit`
- `before.bank.withdraw` / `after.bank.withdraw`
- `before.bank.transfer` / `after.bank.transfer`
- `before.inventory.buy` / `after.inventory.buy`
- `before.inventory.sell` / `after.inventory.sell`
- `before.inventory.equip` / `after.inventory.equip`
- `before.inventory.unequip` / `after.inventory.unequip`
- `before.inventory.use` / `after.inventory.use`

#### Hook Payload Validation

Each hook in `HookRegistry` stores a schema (field => expected type). When
`validatePayload()` is called, it checks all required fields and types.
Validation errors are returned as an array (empty = valid). Deprecated hooks
trigger warnings. This runs only in non-production environments.

#### Frontend Plugin System

**`plugin-bus.ts`** — Singleton event bus for frontend plugin registration:
- `registerHeaderLink(link)` — Add navigation items
- `registerSidebarWidget(widget)` — Add sidebar components
- `registerDashboardWidget(widget)` — Add dashboard widgets
- `registerSettingsTab(tab)` — Add settings tabs
- `on(event, callback)` / `emit(event, data)` — Custom event pub/sub
- Each registration returns an unregister function for cleanup

**`plugin-loader.ts`** — Dynamic module loader:
- `loadPluginModule(slug)` — Lazy-imports `@/plugins/{slug}/index.ts`
- `loadPluginRoutes()` — Loads routes from `@/plugins/{slug}/routes.ts`
- `getPluginRoutes()` — Aggregate routes from all loaded plugins
- `importPluginComponent(slug, path)` — Lazy-load Vue components
- Supports caching, deduplication, and error handling

**`PluginSlot.vue`** — Render component for dynamic plugin content at named slots

**`stores/hub.ts`** — Pinia store bridging backend plugin registry to frontend:
- `fetchPlugins()` — GET `/api/v1/plugins/enabled`
- `slots` — Maps slot names to available components
- `menus` — Maps plugin navigation entries
- Cache with 5-minute TTL

**`stores/plugins.ts`** — Plugin management store for the admin panel.

---

## 6. License System

### 6.1 Key Format

```
LCP-{TIER}-{ENCODED_PAYLOAD}-{SIGNATURE}
```

- `LCP` — Literal prefix
- `TIER` — License tier (e.g., `BASIC`, `PRO`, `ENTERPRISE`)
- `PAYLOAD` — Base64-encoded JSON with domain, tier, issued date, expiry, customer info
- `SIGNATURE` — RSA-256 signature of the payload (base64-encoded)

### 6.2 Key Management

- **Signing**: RSA private key (kept only in Lindon's master environment,
  referred to via `LCP_LICENSE_PRIVATE_KEY` env var or `license_private.pem` file)
- **Verification**: RSA public key (embedded in `LicenseService::PUBLIC_KEY`,
  also loadable from `license_public.pem`)
- **License keys table**: `license_keys` in MySQL, managed via admin UI
- **Activation callback**: POST `/api/license/callback` (unversioned, server-to-server)
  secured by `LICENSE_CALLBACK_SECRET` HMAC

### 6.3 Key Hierarchy

| Location | Key | Purpose |
|----------|-----|---------|
| Master env | `LCP_LICENSE_PRIVATE_KEY` | Signing (only Lindon) |
| Customer | `license_public.pem` or `storage/license_key` | Verification |
| Master server | `/license/callback` endpoint | Activation validation |

---

## 7. API Architecture

All API routes are defined in `backend/routes/api.php` under the prefix
`/api/v1/`.

### 7.1 Unversioned Endpoints

These URLs must never change (external callbacks):

| Method | Path | Purpose | Rate Limit |
|--------|------|---------|------------|
| POST | `/api/license/callback` | License activation callback | 10/min |

### 7.2 Public Endpoints

| Method | Path | Purpose | Rate Limit |
|--------|------|---------|------------|
| GET | `/api/v1/plugins/enabled` | Active plugin list | — |
| GET | `/api/v1/license/status` | License status | — |
| POST | `/api/v1/license/activate` | Activate license | — |
| POST | `/api/v1/register` | User registration | 10/min |
| POST | `/api/v1/login` | User login | 10/min |
| POST | `/api/v1/forgot-password` | Send reset link | 10/min |
| GET | `/api/v1/oauth/providers` | List OAuth providers | — |
| GET | `/api/v1/community/discussions` | Public discussions | — |

### 7.3 Authenticated Endpoints (`auth:sanctum`)

| Method | Path | Purpose |
|--------|------|---------|
| GET | `/api/v1/user` | Current user info |
| POST | `/api/v1/logout` | Revoke current token |
| POST | `/api/v1/user/change-password` | Change password |
| GET/POST | `/api/v1/community/discussions` | CRUD discussions/replies |
| GET | `/api/v1/dashboard` | Dashboard data |
| GET | `/api/v1/stats/` | Player statistics |
| GET | `/api/v1/activity` | Activity log |
| GET | `/api/v1/notifications` | User notifications |
| POST | `/api/v1/ws/auth` | WebSocket channel auth |
| GET | `/api/v1/emojis` | Emoji data |
| POST | `/api/v1/format/preview` | BBCode preview |

### 7.4 2FA Endpoints

| Method | Path | Auth |
|--------|------|------|
| POST | `/api/v1/2fa/verify` | Public (login flow) |
| GET | `/api/v1/2fa/status` | Auth required |
| POST | `/api/v1/2fa/setup` | Auth required |
| POST | `/api/v1/2fa/confirm` | Auth required |
| POST | `/api/v1/2fa/disable` | Auth required |
| POST | `/api/v1/2fa/recovery-codes` | Auth required |

### 7.5 OAuth Endpoints

| Method | Path | Auth |
|--------|------|------|
| GET | `/api/v1/oauth/{provider}/redirect` | Public |
| GET | `/api/v1/oauth/{provider}/callback` | Public |
| GET | `/api/v1/oauth/linked` | Auth required |
| DELETE | `/api/v1/oauth/{provider}/unlink` | Auth required |

### 7.6 Admin Endpoints (`auth:sanctum + role:admin|moderator + verify.license`)

All admin routes are nested under `/api/v1/admin/`.

| Prefix | Purpose |
|--------|---------|
| `/stats` | Dashboard statistics |
| `/license/*` | License management (generate, revoke, keys) |
| `/plugins/*` | Plugin lifecycle management |
| `/users/*` | User CRUD, ban/unban |
| `/roles` | Role CRUD |
| `/permissions` | Permission listing |
| `/settings/*` | System settings, OAuth config, security |
| `/webhooks/*` | Webhook CRUD, deliveries, retry |
| `/email/*` | Email settings, templates, preview, test |
| `/error-logs/*` | Error log browsing, resolve, clear |
| `/notifications/*` | Admin notification management |
| `/cache/*` | Cache clear, warm-up |
| `/staff-chat/*` | Real-time staff messaging |
| `/backups/*` | Backup management, restore |
| `/system/health` | System health monitoring |
| `/developer/*` | Hook registry & metrics introspection |
| `/api-keys/*` | API key CRUD, analytics, logs |
| `/sidebar` | Dynamic admin menu |
| 16 configurable type tables | `item-rarities`, `property-types`, `crime-difficulties`, etc. |

---

## 8. Data Flow

### 8.1 Request Lifecycle (SPA → Backend)

```
Browser (Vue SPA)
  │
  ├── Axios request
  │   └── /api/v1/...
  │       │
  │       ▼
  │   Laravel API Gateway
  │       │
  │       ├── Middleware Stack
  │       │   ├── CORS (config/cors.php)
  │       │   ├── Sanctum (auth:sanctum)
  │       │   ├── Rate Limiter (throttle)
  │       │   ├── Role Check (role:admin|moderator) ── if admin route
  │       │   └── License Verify (verify.license)  ── if admin route
  │       │
  │       ▼
  │   Controller
  │       │
  │       ▼
  │   Service Layer (28 services)
  │       │
  │       ├── Action Pipeline ── for game actions
  │       │   ├── ValidateActionPipe
  │       │   ├── RateLimitPipe
  │       │   ├── BeforeHooksPipe (fires before.* hook)
  │       │   ├── ExecutePipe (core logic)
  │       │   ├── CooldownPipe
  │       │   ├── AfterHooksPipe (fires after.* hook)
  │       │   └── LogActionPipe
  │       │
  │       ▼
  │   Eloquent Model
  │       │
  │       ▼
  │   MySQL 8.0
  │
  └── JSON Response (API Resource)
      └── Vue component re-renders
```

### 8.2 WebSocket Flow

```
Game Event (backend)
  │
  ├── WebSocketService::broadcast()
  │   ├── Stores message in cache (polling fallback)
  │   └── Dispatches WebSocketBroadcast event
  │       │
  │       ├── Laravel Reverb (Pusher protocol)
  │       │   └── Private/Presence/Public channel
  │       │       │
  │       │       ▼
  │       │   Browser WebSocket client (services/websocket.ts)
  │       │       │
  │       │       ├── ConnectionState machine
  │       │       ├── Automatic reconnection
  │       │       ├── Heartbeat every 30s
  │       │       └── Message routing to Pinia stores
  │       │
  │       └── Polling fallback
  │           └── POST /api/v1/ws/poll (periodic)
  │               └── Cache-based message retrieval
  │
  └── Vue Components react via stores
```

### 8.3 Plugin Registration Flow

```
1. Server starts → PluginServiceProvider::boot()
2.   Scan app/Plugins/ directory
3.   For each plugin directory:
4.     Read plugin.json
5.     Validate required fields (name, slug, version)
6.     Instantiate Plugin class
7.     Call register() → boot()
8.     Register routes (web/api/admin)
9.     Load hooks.php → register callbacks
10.    Cache in PluginRegistry
11. Frontend loads → GET /api/v1/plugins/enabled
12.   hub store caches plugin list
13.   plugin-loader dynamically imports Vue modules
14.   Router addRoutes() for each plugin
15.   PluginSlot components rendered at slot positions
```

---

## 9. Database Schema

### 9.1 Core Tables (42 migrations)

| Table | Purpose | Key Columns |
|-------|---------|-------------|
| `users` | User accounts | id, name, email, password, two_factor_secret, two_factor_recovery_codes, last_login_at, last_active_at, force_password_change |
| `player_profiles` | Game-specific player data | user_id (FK), avatar, bio, level, experience, respect, health, energy, nerve |
| `plugins` | Plugin registry | id, slug, name, version, description, enabled, author, license_required |
| `installed_plugins` | Installation state | id, plugin_id (FK), version, enabled, installed_at, last_activated_at |
| `plugin_metadata` | Plugin key-value metadata | id, plugin_id (FK), key, value |
| `permissions` | Spatie permissions | id, name, guard_name |
| `roles` | Spatie roles | id, name, guard_name |
| `model_has_roles` | User-role assignments | role_id, model_type, model_id |
| `model_has_permissions` | Direct permission assignments | permission_id, model_type, model_id |
| `role_has_permissions` | Role-permission assignments | permission_id, role_id |
| `settings` | Key-value system settings | id, key, value, type |
| `notifications` | User notifications | id, user_id, type, data, read_at |
| `activity_logs` | Activity records | id, user_id, action, details, ip_address |
| `error_logs` | Captured errors | id, level, message, trace, resolved_at |
| `license_keys` | License key storage | id, key, tier, domain, expires_at, revoked_at |
| `api_keys` | API access keys | id, user_id, name, key, secret, permissions, last_used_at |
| `oauth_providers` | Linked OAuth accounts | id, user_id, provider, provider_id, avatar |
| `personal_access_tokens` | Sanctum tokens | id, tokenable_type, tokenable_id, name, token, abilities |
| `webhooks` | Outgoing webhook configs | id, name, url, events, secret, enabled |
| `webhook_deliveries` | Webhook delivery log | id, webhook_id, status, request, response |
| `player_bans` | Player bans | id, user_id, reason, banned_by, expires_at |
| `ip_bans` | IP address bans | id, ip_address, reason, banned_by |
| `player_warnings` | Player warnings | id, user_id, reason, warned_by |
| `staff_chat_messages` | Staff internal chat | id, user_id, message, channel |
| `staff_chat_read_status` | Read state tracking | id, user_id, last_read_at |
| `community_discussions` | Forum discussions | id, user_id, title, content, category_id, pinned, locked |
| `community_discussion_replies` | Discussion replies | id, discussion_id, user_id, content |
| `email_settings` | Email configuration | id, key, value |
| `email_templates` | Email templates | id, name, subject, body, type |
| `cache` | Laravel cache store | key, value, expiration |
| `jobs` | Queue jobs | id, queue, payload, attempts |
| `sessions` | Session data | id, user_id, payload, last_activity |

### 9.2 Configurable Type Tables (16)

These tables are CRUD-managed via admin controllers and serve as extensible
lookup/enum tables for game plugins:

| Table | Admin Controller | Example Records |
|-------|-----------------|-----------------|
| `item_rarities` | ItemRarityController | Common, Uncommon, Rare, Epic, Legendary |
| `property_types` | PropertyTypeController | House, Villa, Mansion, Penthouse, Castle |
| `announcement_types` | AnnouncementTypeController | Update, Event, Maintenance, Alert |
| `crime_difficulties` | CrimeDifficultyController | Easy, Medium, Hard, Expert, Impossible |
| `casino_game_types` | CasinoGameTypeController | Slots, Poker, Blackjack, Roulette |
| `company_industries` | CompanyIndustryController | Tech, Healthcare, Finance, Retail |
| `stock_sectors` | StockSectorController | Technology, Energy, Healthcare, Finance |
| `course_skills` | CourseSkillController | Hacking, Strength, Speed, Charisma |
| `course_difficulties` | CourseDifficultyController | Beginner, Intermediate, Advanced, Expert |
| `achievement_stats` | AchievementStatController | Kills, Heists, Jobs, Money Earned |
| `mission_frequencies` | MissionFrequencyController | Daily, Weekly, Monthly, One-Time |
| `mission_objective_types` | MissionObjectiveTypeController | Kill, Collect, Deliver, Escort |
| `bounty_statuses` | BountyStatusController | Active, Completed, Expired, Cancelled |
| `lottery_statuses` | LotteryStatusController | Open, Drawn, Claimed, Expired |
| `item_effect_types` | ItemEffectTypeController | Heal, Buff, Damage, Shield, Poison |
| `item_modifier_types` | ItemModifierTypeController | Strength, Agility, Defense, Luck |

### 9.3 Indexes & Performance

- `users.email` unique index
- `license_keys.key` unique index
- Composite performance indexes on `users` and `license_keys` added in
  migration `2026_02_11_000000`
- Spatie permission cache with 24-hour TTL
- Plugin discovery cache with 1-hour TTL

---

## 10. Frontend Architecture

### 10.1 Layer Overview

```
frontend/src/
  App.vue                      # Root component
  main.ts                      # App bootstrap: router, pinia, plugins
  │
  ├── stores/                  # Pinia state management
  │   ├── auth.ts              #   Auth state, login/logout/register
  │   ├── hub.ts               #   Plugin hub (active plugins, slots, menus)
  │   ├── plugins.ts           #   Plugin management (admin panel)
  │   ├── notifications.ts     #   User notification management
  │   └── user.ts              #   User profile state
  │
  ├── router/
  │   └── index.ts             # Vue Router with dynamic plugin routes
  │
  ├── services/
  │   ├── api.ts               # Axios client (595 lines)
  │   │   ├── Request deduplication
  │   │   ├── Response caching (5-min TTL)
  │   │   ├── Request cancellation (AbortController)
  │   │   ├── Automatic token refresh
  │   │   └── Error handling & retry
  │   │
  │   ├── websocket.ts          # Reverb WebSocket client (488 lines)
  │   │   ├── Connection state machine
  │   │   ├── Automatic reconnection with backoff
  │   │   ├── Heartbeat interval (30s)
  │   │   ├── Presence channel support
  │   │   └── Polling fallback
  │   │
  │   ├── plugin-bus.ts         # Frontend plugin hook system (239 lines)
  │   │   └── Singleton event bus: header links, sidebar/dashboard widgets,
  │   │       settings tabs, custom event pub/sub
  │   │
  │   └── plugin-loader.ts      # Dynamic plugin module loader (225 lines)
  │       └── Lazy-loads plugin Vue modules, routes, and components
  │
  ├── components/
  │   ├── layout/              # CoreLayout.vue (main shell)
  │   ├── ui/                  # UI primitives (buttons, modals, inputs)
  │   ├── icons/               # SVG/Vue icon components
  │   ├── peptide/             # PBBG game components
  │   └── PluginSlot.vue       # Dynamic slot renderer for plugins
  │
  ├── composables/
  │   ├── useWebSocket.ts      # WebSocket composable
  │   ├── usePluginRoutes.ts   # Plugin route composable
  │   ├── useLazyLoad.ts       # Intersection observer lazy loading
  │   ├── useToast.ts          # Toast notification composable
  │   └── useServiceWorker.ts  # PWA / offline support
  │
  ├── views/
  │   ├── admin/               # Admin panel views
  │   ├── LoginView.vue        # Login page (supports 2FA redirect)
  │   ├── RegisterView.vue     # Registration page
  │   ├── PeptideCommunityView.vue  # Primary game/community view
  │   └── ...
  │
  └── types/
      ├── api.ts, user.ts, plugin.ts, plugin-route.ts,
      ├── notification.ts, router.ts, websocket.ts
      └── vue-shim.d.ts
```

### 10.2 Auth Flow (Frontend)

```
User visits /login
  → LoginView.vue renders
  → Submits credentials → api.post('/api/v1/login')
  │
  ├── Success → { user, token }
  │   ├── Token stored in localStorage
  │   ├── Axios default header set: Authorization: Bearer {token}
  │   └── Router redirects to /dashboard
  │
  ├── 2FA Required → { two_factor_required: true, challenge_token }
  │   ├── Challenge token stored in sessionStorage
  │   └── Router redirects to /2fa/verify
  │       └── POST /api/v1/2fa/verify with { challenge_token, code }
  │
  └── Failure → Error displayed
```

### 10.3 Route Architecture

Routes are defined in `frontend/src/router/index.ts` with lazy-loading via
dynamic imports. Plugin routes are added dynamically:

```typescript
// After plugins load:
pluginRoutes.forEach(route => router.addRoute('core', route))
```

The router uses `createWebHistory()` and Vue Router's `RouteRecordRaw` type
extended with `RouteMeta` for custom page metadata.

---

## 11. Security

### 11.1 Authentication Flow

```
┌─────────┐     ┌──────────────┐     ┌──────────┐     ┌─────────┐
│  Browser │────▶│  /api/v1/   │────▶│ Sanctum  │────▶│ Laravel │
│  SPA     │     │  login      │     │ Auth     │     │ Session │
└─────────┘     └──────────────┘     └──────────┘     └─────────┘
     │                                   │
     │  Token issued                     │ SPA session via
     │  (Bearer header)                  │ SANCTUM_STATEFUL_DOMAINS
     │                                   │
     └── All subsequent API calls ───────┘
         with Bearer token or cookie
```

Sanctum is configured to support both SPA cookie-based auth (stateful domains)
and token-based auth (mobile/external clients).

### 11.2 Middleware Chain

```
Request → CORS → Throttle → Sanctum → Role Check → License Verify → Controller
```

| Layer | Protection |
|-------|-----------|
| CORS | Configured in `config/cors.php` |
| Throttle | `api` (60/min), `game-actions` (20/min), `combat-actions` (10/min), auth routes (10/min) |
| Sanctum | Token-based with expiry, token abilities, session auth |
| Role Check | Spatie `role:admin\|moderator` middleware |
| License Verify | Custom `verify.license` middleware for admin routes |

### 11.3 Permission System (Spatie)

- **Roles**: `admin`, `moderator`, `user` (configurable via admin panel)
- **Permissions**: Granular per-action permissions assigned to roles
- **Plugin permissions**: Each plugin can define its own permission groups via
  `plugin.json` (`permissions` key), auto-registered on install
- **API key permissions**: API keys have granular permission scopes
- **Cache**: Spatie permission cache with 24-hour TTL; auto-flushes on updates

### 11.4 License Enforcement

- Admin routes protected by `verify.license` middleware
- License verified via RSA-256 signature check
- License keys stored in `license_keys` table
- Activation callback validates against master server
- License tiers control feature availability
- `LICENSE_CALLBACK_SECRET` HMAC secures the callback endpoint
- Warning logged if `LICENSE_CALLBACK_SECRET` is empty in non-local environments

### 11.5 2FA Enforcement

- Google Authenticator TOTP via `pragmarx/google2fa`
- QR code generation via `simplesoftwareio/simple-qrcode`
- Recovery codes for account recovery
- 2FA enforced at login (challenge token flow)
- Admin-configurable 2FA requirement per role
- Per-user 2FA status tracked in `users.two_factor_secret`

### 11.6 Additional Security Measures

- **Password security**: Forced password change support, reset tokens with expiry
- **User banning**: Player bans + IP bans with optional expiry
- **Rate limiting**: Tiered rate limiters for API, game actions, combat, auth
- **Error handling**: `GameException`, `InsufficientFundsException`,
  `PluginPermissionException` — no stack traces leaked to API responses
- **Model strictness**: `Model::shouldBeStrict()` enabled in non-production
  (catches N+1, lazy loading, missing attributes)
- **API keys**: Revocable with usage logging, granular scopes, secret regeneration
- **Webhook signing**: Outgoing webhooks signed with per-webhook secrets,
  delivery logging with retry support
