# Contributing to LaravelCP

Thank you for contributing to **LaravelCP**, a PBBG platform built with Laravel 11 + Vue 3.

This project is proprietary. These guidelines exist for developers who have been granted access to the repository.

---

## 1. Code of Conduct

- Treat all contributors and community members with respect.
- Harassment, discrimination, or toxic behaviour will not be tolerated.
- Disagreements are fine; personal attacks are not.
- If you witness or experience unacceptable behaviour, contact the project maintainer privately.

---

## 2. Getting Started

```bash
# Clone the repository
git clone https://github.com/Lindon11/LaravelCP.git
cd LaravelCP

# Start the Docker environment
docker compose up -d --build

# Set up the backend
cp backend/.env.example backend/.env
docker compose exec backend php artisan key:generate
docker compose exec backend php artisan migrate --force
docker compose exec backend php artisan app:install --force \
    --admin-username=admin \
    --admin-email=admin@example.com \
    --admin-password=admin123

# Generate a development license
docker compose exec backend php artisan license:setup
docker compose exec backend php artisan license:generate \
    --domain="*" \
    --tier=standard \
    --customer="Local Development" \
    --email="admin@example.com" \
    --expires=lifetime

# Install frontend dependencies (container handles this on build)
# Access: http://localhost:5175
```

All development happens in a feature branch — never commit directly to `main`.

---

## 3. Development Workflow

### 3.1 Branch Naming

| Pattern     | Example                        |
|-------------|--------------------------------|
| `feature/*` | `feature/player-profile-trophies` |
| `fix/*`     | `fix/energy-refill-off-by-one` |
| `chore/*`   | `chore/update-composer-deps`   |

### 3.2 Commit Messages (Conventional Commits)

```
<type>(<scope>): <description>
```

Types: `feat`, `fix`, `chore`, `refactor`, `test`, `docs`, `style`, `perf`.

```
feat(api): add player trophy endpoint
fix(pipeline): prevent cooldown reset on validation failure
chore(deps): bump laravel/framework to 11.40
```

### 3.3 Atomic Commits

Keep commits small and focused on a single concern. A commit should represent one logical change — not a bundle of unrelated edits.

### 3.4 Rebasing vs Merging

**Always rebase** feature branches onto `main` before opening a PR. This keeps the history linear and readable.

```bash
git checkout feature/my-feature
git fetch origin
git rebase origin/main
```

If conflicts arise during rebase, resolve them and continue with `git rebase --continue`. Force-push the rebased branch: `git push --force-with-lease`.

---

## 4. Coding Standards

### 4.1 PHP

- Follow **PSR-12** coding style.
- **Laravel Pint** is enforced — run it before committing:

```bash
docker compose exec backend ./vendor/bin/pint
docker compose exec backend ./vendor/bin/pint --test
```

- Classes: `PascalCase` (e.g. `PlayerStatsController`).
- Methods and functions: `camelCase` (e.g. `getActivePlayers()`).
- Routes: `kebab-case` paths with `camelCase` route names (e.g. `/api/v1/player-stats`, `name: 'playerStats'`).
- Database columns: `snake_case` (e.g. `last_login_at`, `player_profile_id`).
- Tables: plural `snake_case` (e.g. `player_profiles`, `activity_logs`).
- Controllers live in `app/Core/Http/Controllers/`; services in `app/Core/Services/`.
- Use type hints and return types on all methods.

### 4.2 Frontend

- **ESLint + oxlint** — run both before committing:

```bash
docker compose exec frontend npm run lint
```

- **TypeScript strict mode** is enabled — no `any` unless absolutely necessary and annotated.
- **Vue: Composition API with `<script setup lang="ts">`**. Avoid the Options API.
- Components in `PascalCase`, files in `kebab-case` (e.g. `BaseButton.vue` in `components/ui/`).
- Pinia stores use the composition/function style (`defineStore('name', () => { ... })`).
- Existing patterns (e.g. `satisfies RouteMeta` on route records) should be followed for consistency.

---

## 5. Testing Requirements

- **Write tests for new features.** Every endpoint, service method, and non-trivial frontend component should have tests.
- **Ensure existing tests pass** before submitting your branch.

```bash
# Backend (PHPUnit)
docker compose exec backend php artisan test

# Frontend unit (Vitest)
docker compose exec frontend npm run test:unit

# Frontend E2E (Playwright) — run selectively as needed
docker compose exec frontend npm run test:e2e
```

Test files mirror their source counterparts (`tests/Feature/`, `tests/Unit/` for backend; `src/**/*.spec.ts` for frontend). Use Laravel's `RefreshDatabase` trait for feature tests that touch the database.

---

## 6. Pull Request Process

1. Create your branch from `main`: `git checkout -b feature/my-feature`.
2. Make changes, commit atomically with conventional messages.
3. Rebase onto the latest `main`:

```bash
git fetch origin
git rebase origin/main
```

4. Push your branch: `git push origin feature/my-feature`.
5. Open a PR against `main` from your branch.
6. In the PR description:
   - Summarise what the change does and why.
   - Reference any related issues (e.g. `Closes #42`).
   - Note if the PR introduces new routes, database migrations, or plugin hooks.
7. If CI is configured for the project, ensure it passes. If not, run the verification commands yourself and paste the results.
8. Request review from the project maintainer. Do not merge your own PR.

---

## 7. Plugin Development Guidelines

### 7.1 Structure

Each plugin lives at `backend/app/Plugins/{slug}/`:

```
{slug}/
├── plugin.json              # Manifest
├── {Slug}Plugin.php         # Plugin class
├── hooks.php                # Hook registrations
├── routes/                  # api.php, web.php, admin.php
├── Controllers/
├── Models/
├── database/migrations/
├── views/
├── lang/
└── assets/
```

Scaffold new plugins with:

```bash
docker compose exec backend php artisan make:plugin my-plugin
```

### 7.2 Hook Usage

Use the `Hook` facade in `hooks.php`:

```php
use App\Facades\Hook;

// Action hook (side-effect, no return)
Hook::register('user.created', function ($user) {
    $user->setManyPluginMeta('my-plugin', ['joined_at' => now()]);
}, 10);

// Filter hook (modifies and returns data)
Hook::register('admin.sidebar', function ($sections) {
    $sections[] = ['id' => 'my-plugin', 'label' => 'My Plugin'];
    return $sections;
}, 10);
```

Hooks follow a `before.{action}` / `after.{action}` naming convention in the action pipeline, and `{noun}.{verb}` for lifecycle hooks (e.g. `economy.credit`, `OnPlayerLogin`).

### 7.3 Frontend Slot System

Plugins inject Vue components into the frontend via named slots. Declare them in `plugin.json`:

```json
"frontend": {
    "slots": {
        "dashboard-widget": ["GoldWidget.vue"],
        "header-link": ["MyNav.vue"]
    }
}
```

In templates, slots render with `<PluginSlot slotName="dashboard-widget" />`. Runtime registration uses the `plugin-bus` service (`registerHeaderLink`, `registerDashboardWidget`, etc.). Each registration returns an unregister function — call it on component unmount to avoid leaks.

---

## 8. Security

- **Never commit secrets.** Environment files (`.env`), `.pem` private keys, and `LICENSE_CALLBACK_SECRET` values must never be committed. The project's `.gitignore` covers most of these; double-check before staging.
- **Report security issues privately.** Do not open a public issue for security vulnerabilities. Contact the maintainer directly.
- **License key handling.** License keys follow the format `LCP-{TIER}-{PAYLOAD}-{SIGNATURE}`. They are generated via `php artisan license:generate` and validated using RSA-256. Never share production license keys. For local development, generate a `--domain="*" --expires=lifetime` key.
- API tokens, OAuth secrets, and webhook secrets should be rotated periodically and never logged.

---

*Last updated: July 2026*
