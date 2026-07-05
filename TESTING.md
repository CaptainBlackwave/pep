# Testing Guide — LaravelCP

## 1. Backend Testing (PHPUnit)

### Running Tests

```bash
# Run all tests (inside Docker container)
docker compose exec backend php artisan test

# Run all tests (locally, if PHP installed)
php artisan test

# Run only unit tests
php artisan test --testsuite=Unit

# Run only feature tests
php artisan test --testsuite=Feature

# Run a specific test file
php artisan test --filter=AuthRegistrationTest

# Run a specific test method
php artisan test --filter=test_user_can_register_with_valid_data

# Stop on first failure
php artisan test --stop-on-failure
```

### Test Structure

Two test suites are defined in `phpunit.xml`:

- **Unit** (`tests/Unit/`) — Test classes in isolation. Mock external dependencies. No database required (unless testing a model method that queries).
- **Feature** (`tests/Feature/`) — Test HTTP endpoints, full request/response lifecycle. Database is always used.

| Suite | Speed | DB | When to use |
|-------|-------|----|-------------|
| Unit | Fast | Not required | Pure logic: services, resolvers, pipelines |
| Feature | Slower | RefreshDatabase | API routes, auth, integration |

### Test Database

Configured in `phpunit.xml`:

```xml
<env name="DB_DATABASE" value="peptide_community_testing" force="true"/>
<env name="DB_CONNECTION" value="mysql"/>
```

Tests run against a dedicated MySQL database `peptide_community_testing`. Feature tests use the `RefreshDatabase` trait, which migrates and rolls back between each test.

### Test Base Class

`tests/TestCase.php` extends Laravel's base `TestCase` and auto-seeds `admin` and `moderator` roles on setup (silently ignored if permission tables don't exist).

### Writing Feature Tests

```php
namespace Tests\Feature;

use App\Core\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MyApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_access_endpoint(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/my-endpoint');

        $response->assertOk()
            ->assertJsonStructure(['data' => ['id', 'name']]);
    }
}
```

#### Common assertions used in the codebase

| Method | Purpose |
|--------|---------|
| `->assertOk()` | 200 status |
| `->assertStatus(422)` | Validation error |
| `->assertJsonStructure([...])` | Shape validation |
| `->assertJsonFragment([...])` | Partial match |
| `->assertJsonPath('data.0.slug', $value)` | Nested assertion |
| `->assertDatabaseHas('table', [...])` | Row exists in DB |
| `->assertJsonValidationErrors(['field'])` | Validation errors |

#### Auth helpers

```php
// Authenticate a user with Sanctum
$user = User::factory()->create();
Sanctum::actingAs($user);

// Assign roles for policy tests
$user->assignRole('admin');
$user->assignRole('moderator');
```

### Testing the License Service

The `LicenseServiceTest` skips tests when no private key is available:

```php
if (!LicenseService::canGenerate()) {
    $this->markTestSkipped('Private key not available — cannot generate licenses.');
}
```

Set `LCP_LICENSE_PRIVATE_KEY` env var (or ensure the key file path is valid) to enable license generation in tests. License keys can be generated with custom domain, tier, and expiry.

### Testing Plugins

Two test files cover the plugin system:

- `tests/Feature/PluginIntegrationTest.php` — End-to-end: plugin discovery, manifest generation, frontend integration. Runs against actual plugin files on disk.
- `tests/Unit/PluginManagerServiceTest.php` — Isolated unit tests using Mockery for file-system mocking and reflection for private method access.

### Factories & Seeders

#### Factories

Located in `database/factories/`. Use them in tests:

```php
User::factory()->create();
User::factory()->create(['is_banned' => true]);
User::factory()->count(3)->create();
```

Custom factories exist for: `User`, `Player`, `Gang`, `Crime`, `CrimeAttempt`.

#### Seeders

Located in `database/seeders/`. Run in tests:

```php
$this->seed(\Database\Seeders\RolePermissionSeeder::class);
$this->seed(\Database\Seeders\TestUserSeeder::class);
```

Important seeders: `RolePermissionSeeder` (always needed for role-based tests), `PluginSeeder`, `DatabaseSeeder`.

### Code Coverage

```bash
# Generate coverage report (text output)
docker compose exec backend php artisan test --coverage

# Generate HTML report
docker compose exec backend php artisan test --coverage --coverage-html=coverage
```

Coverage includes all files under `app/` as configured in `phpunit.xml`. Requires Xdebug or PCOV enabled in the Docker container.

### Mocking

The project uses Mockery (integrated via PHPUnit). Laravel's `File` facade is commonly mocked:

```php
File::shouldReceive('exists')->andReturn(true);
File::shouldReceive('isDirectory')->andReturn(true);
```

Use `RefreshDatabase` instead of mocking DB calls in feature tests — it's more reliable and tests real query logic.

---

## 2. Frontend Testing (Vitest + Playwright)

### Running Tests

```bash
# Run all unit tests
npm run test:unit

# Run with UI
npx vitest --ui

# Watch mode
npm run test:unit -- --watch

# Run a specific test file
npx vitest src/components/__tests__/BaseButton.spec.ts

# Run tests matching a pattern
npx vitest -t "LoginView"

# Type checking
npm run type-check

# Run E2E tests
npm run test:e2e

# Run E2E in headed mode (see browser)
npx playwright test --headed
```

### Vitest Configuration

`vitest.config.ts` merges the Vite config with:

```ts
test: {
  environment: 'jsdom',
  exclude: [...configDefaults.exclude, 'e2e/**'],
  root: fileURLToPath(new URL('./', import.meta.url)),
}
```

- Uses `jsdom` environment (no real browser for unit tests).
- E2E tests (under `e2e/`) are excluded from unit runs.

### Writing Component Tests

Located at `src/components/__tests__/`. Pattern used across the codebase:

```ts
import { describe, it, expect } from 'vitest'
import { mount } from '@vue/test-utils'
import BaseButton from '@/components/ui/BaseButton.vue'

describe('BaseButton', () => {
  it('renders a button element', () => {
    const wrapper = mount(BaseButton)
    expect(wrapper.find('button').exists()).toBe(true)
  })

  it('emits click event when clicked', async () => {
    const wrapper = mount(BaseButton)
    await wrapper.find('button').trigger('click')
    expect(wrapper.emitted('click')).toBeTruthy()
  })

  it('renders slot content', () => {
    const wrapper = mount(BaseButton, {
      slots: { default: 'Click me' }
    })
    expect(wrapper.text()).toContain('Click me')
  })

  it('applies props correctly', () => {
    const wrapper = mount(BaseButton, {
      props: { variant: 'danger', size: 'lg', disabled: true }
    })
    expect(wrapper.find('button').classes()).toContain('danger')
    expect(wrapper.find('button').attributes('disabled')).toBeDefined()
  })
})
```

### Testing Pinia Stores

Located at `src/stores/__tests__/`. Set up Pinia fresh before each test:

```ts
import { setActivePinia, createPinia } from 'pinia'
import { useAuthStore } from '@/stores/auth'

// Mock the API module
vi.mock('@/services/api', () => ({
  default: { post: vi.fn(), get: vi.fn() },
}))

import api from '@/services/api'

describe('Auth Store', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    vi.clearAllMocks()
  })

  it('starts with null user', () => {
    const auth = useAuthStore()
    expect(auth.user).toBeNull()
  })

  it('handles successful login', async () => {
    api.post.mockResolvedValueOnce({
      data: { user: { id: 1, username: 'test' }, token: 'abc' }
    })

    const auth = useAuthStore()
    const result = await auth.login({ email: 'a@b.com', password: 'secret' })

    expect(result).toBe(true)
    expect(auth.isAuthenticated).toBe(true)
  })

  it('handles failed login', async () => {
    api.post.mockRejectedValueOnce({
      response: { data: { message: 'Invalid credentials', errors: {} } }
    })

    const auth = useAuthStore()
    const result = await auth.login({ email: 'bad@b.com', password: 'wrong' })

    expect(result).toBe(false)
    expect(auth.error).toBe('Invalid credentials')
  })
})
```

Key patterns:
- Use `vi.mock('@/services/api', ...)` to mock API calls (hoisted by Vitest).
- Call `setActivePinia(createPinia())` in `beforeEach` to isolate store state.
- Clear mocks with `vi.clearAllMocks()`.
- Use `vi.hoisted()` for mock references when dealing with circular dependencies.

### Testing Vue Router Navigation

Views that use `vue-router` need a router instance. Pattern from `LoginView.spec.js`:

```ts
import { createRouter, createMemoryHistory } from 'vue-router'

function makeRouter() {
  return createRouter({
    history: createMemoryHistory(),
    routes: [
      { path: '/login', component: LoginView },
      { path: '/dashboard', component: { template: '<div>Dashboard</div>' } },
    ],
  })
}

// In the test:
const router = makeRouter()
await router.push('/login')
await router.isReady()

const wrapper = mount(LoginView, {
  global: { plugins: [pinia, router] },
})
```

Assert navigation:

```ts
expect(router.currentRoute.value.path).toBe('/dashboard')
```

### Mocking API Calls

Two approaches are used:

**1. Store-level mocking** (unit tests) — mock `@/services/api` module with `vi.mock`:

```ts
vi.mock('@/services/api', () => ({
  default: { post: vi.fn(), get: vi.fn() },
}))
```

**2. E2E mocking** — intercept network requests with Playwright's `page.route`:

```ts
await page.route('**/api/v1/login**', (route) =>
  route.fulfill({
    status: 200,
    contentType: 'application/json',
    body: JSON.stringify({ user: { id: 1, username: 'test' }, token: 'abc' }),
  }),
)
```

### E2E Tests with Playwright

`playwright.config.ts` configures:

- **Browsers**: Chromium, Firefox, WebKit (parallel by default).
- **Dev server**: Auto-starts `npm run dev` (or `npm run preview` on CI).
- **Base URL**: `http://localhost:5175` (dev) or `http://localhost:4173` (CI/preview).
- **Retries**: 2 on CI, 0 locally.
- **Trace**: captured on first retry.
- **CI detection**: `forbidOnly`, `workers: 1`, `headless` — all auto-configured via `CI` env var.

#### Writing E2E Tests

```ts
import { test, expect, type Page } from '@playwright/test'

test.describe('Login Page', () => {
  test.beforeEach(async ({ page }) => {
    await page.goto('/login')
    await page.evaluate(() => localStorage.clear())
  })

  test('displays the login form', async ({ page }) => {
    await expect(page.locator('#email')).toBeVisible()
    await expect(page.locator('#password')).toBeVisible()
    await expect(page.locator('button[type="submit"]')).toContainText('Sign in')
  })

  test('shows error on failed login', async ({ page }) => {
    await page.route('**/api/v1/login**', (route) =>
      route.fulfill({
        status: 422,
        contentType: 'application/json',
        body: JSON.stringify({ message: 'Invalid credentials', errors: {} }),
      }),
    )

    await page.fill('#email', 'bad@example.com')
    await page.fill('#password', 'wrong')
    await page.click('button[type="submit"]')

    await expect(page.locator('.error-message')).toBeVisible()
  })
})
```

### Type Checking

```bash
npm run type-check
```

Uses `vue-tsc --build` to type-check all Vue SFCs and TypeScript files. Run this as part of CI before tests.

---

## 3. Testing Best Practices

### Naming Conventions

- **Test classes**: Match the class under test, suffixed with `Test`. `WalletService` → `WalletServiceTest`
- **Test methods**: Snake_case describing scenario and expectation: `test_banned_player_is_blocked_before_execute`
- **Frontend describe blocks**: Component name or store name: `describe('Auth Store')`, `describe('BaseButton')`
- **Frontend it/blocks**: Behavior-oriented: `it('starts with null user')`, `it('emits click event when clicked')`

### Test File Organization

| Layer | Location |
|-------|----------|
| PHP Feature tests | `backend/tests/Feature/` |
| PHP Unit tests | `backend/tests/Unit/` |
| Vue Component tests | `frontend/src/components/__tests__/*.spec.ts` |
| Vue View tests | `frontend/src/views/__tests__/*.spec.{ts,js}` |
| Pinia Store tests | `frontend/src/stores/__tests__/*.spec.ts` |
| Service tests | `frontend/src/services/__tests__/*.spec.ts` |
| E2E tests | `frontend/e2e/*.spec.ts` |

### What to Test vs. What Not to Test

**Test:**
- API endpoint behavior (success, validation errors, auth guards, pagination).
- Policy logic (role-based access for each CRUD operation).
- Service methods (edge cases, exceptions, state changes).
- Vue component rendering (props, slots, conditional states, emitted events).
- Pinia store actions (mock API, assert state changes and error handling).
- E2E critical user flows (login, registration, navigation guards).

**Do not test:**
- Laravel/Vue/Pinia internals (they are tested by their own projects).
- Trivial getter methods with no logic.
- CSS styling details (class presence is fine; pixel values are not).
- Third-party library behavior.

### Using Factories for Test Data

Always prefer factories over raw model creation:

```php
// Prefer this:
User::factory()->create(['email' => 'test@example.com']);

// Avoid this:
User::create(['email' => 'test@example.com', 'password' => bcrypt('x'), ...]);
```

Use factory states for common variants:

```php
// When testing banned-user scenarios
User::factory()->create(['is_banned' => true]);

// For role-specific tests
$user = User::factory()->create();
$user->assignRole('admin');
```

---

## 4. Running in Docker

### Executing Tests Inside Containers

```bash
# Run all backend tests
docker compose exec backend php artisan test

# Run with verbose output
docker compose exec backend php artisan test -vvv

# Run lifecycle tests (shared coverage)
docker compose exec backend php artisan test --filter=PluginIntegrationTest
```

The MySQL container (`laravelcp_db`) must be running and healthy before tests execute. The test database `peptide_community_testing` is used per the `phpunit.xml` config — create it if missing:

```bash
docker compose exec mysql mysql -uroot -proot -e "CREATE DATABASE IF NOT EXISTS peptide_community_testing"
```

### Handling Database State

- The `RefreshDatabase` trait migrates the schema before each test and rolls back after. No manual cleanup needed.
- Test data does **not** persist to the development database — a separate `peptide_community_testing` database is used.
- `RefreshDatabase` is used in all feature tests and in unit tests that depend on the database.

---

## 5. Continuous Integration

Tests should be run in CI in the following order:

1. `npm run type-check` — Catch TypeScript errors early.
2. `php artisan test --testsuite=Unit` — Fastest feedback.
3. `php artisan test --testsuite=Feature` — Slower integration suite.
4. `npm run test:unit` — Vue component and store tests.
5. `npm run test:e2e` — Full browser test suite (Chromium, Firefox, WebKit).
6. `php artisan test --coverage` — Verify coverage thresholds.

There is currently no CI pipeline configured for this repository.
