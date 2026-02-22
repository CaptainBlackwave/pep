# Plugin Contract

## 1️⃣ Required Folder Structure

Every plugin **must** follow this structure:

```text
plugin/
├── plugin.json
├── PluginServiceProvider.php
├── routes/
├── database/
├── src/
└── README.md
```

- Anything outside this structure is ignored or rejected.
- All plugin logic, assets, and configuration must reside within these folders/files.

---

## 2️⃣ plugin.json Schema

**This schema is locked and must not change after publication.**

### Required fields

- `name` (string): Human-readable plugin name
- `slug` (string): Unique identifier (lowercase, kebab-case, e.g. `"organized-crime"`)
- `version` (string): Plugin version (semver)
- `author` (string): Plugin author
- `enabled` (boolean): Whether the plugin loads on boot
- `requires.laravel` (string): Minimum compatible Laravel version constraint
- `requires.modules` (object): Other plugin slugs this plugin depends on, keyed by slug with a semver version constraint. Use `{}` for no dependencies.
- `routes.web` (boolean): Plugin registers `routes/web.php`
- `routes.api` (boolean): Plugin registers `routes/api.php`
- `routes.admin` (boolean): Plugin registers `routes/admin.php`

### Optional fields

- `description` (string): Short description
- `permissions` (array of strings): LaravelCP permissions this plugin requires (e.g. `"economy.write"`)
- `settings` (object): UI metadata — `icon`, `color`, `route`, `menu`, `permissions`
- `hooks` (object): Hook names the plugin fires or responds to

### Module dependencies

The `requires.modules` value **must** be an object mapping slugs to semver constraints — never a plain array. This ensures the `SemverResolver` can enforce compatibility at install time.

Supported constraint operators: `^`, `~`, `>=`, `>`, `<=`, `<`, `*` (any version).

```json
"requires": {
    "laravel": "^11.0",
    "modules": {
        "gang": "^3.0.0",
        "inventory": "^3.0.0"
    }
}
```

Plugins with no module dependencies must use an empty object:

```json
"requires": {
    "laravel": "^11.0",
    "modules": {}
}
```

Full example:

```json
{
    "name": "Organized Crime",
    "slug": "organized-crime",
    "version": "3.0.0",
    "description": "Coordinate gang crimes for big rewards and reputation",
    "author": "OpenPBBG",
    "enabled": true,
    "requires": {
        "laravel": "^11.0",
        "modules": { "gang": "^3.0.0" }
    },
    "permissions": ["economy.write"],
    "settings": {
        "icon": "💼",
        "color": "indigo",
        "route": "organized-crimes.index",
        "menu": { "enabled": true, "order": 45, "section": "actions", "parent": null },
        "permissions": { "view": "level:5", "use": "level:5" }
    },
    "hooks": {
        "OnOrganizedCrimeAttempt": true,
        "OnOrganizedCrimeComplete": true,
        "alterModuleData": true,
        "moduleLoad": true
    },
    "routes": { "web": true, "api": false, "admin": false }
}
```

---

## 3️⃣ Permission Model

**Initial permission list (expandable, never remove):**

- `economy.read`
- `economy.write`
- `inventory.read`
- `inventory.write`
- `combat.modify`
- `cooldowns.modify`

You may add more later, but never remove or rename existing permissions.
Plugins must declare all permissions they require in `plugin.json`.

---

## 4️⃣ Hook Registration

Plugins register hook listeners in `hooks.php`, which is auto-loaded by `AutoPluginHookLoader` only when the plugin is `enabled`.

### Two registration formats

**Declarative** (no priority control — all run at default priority `10`):

```php
return [
    'alterCrimeRewards' => function (array $data): array {
        $data['cash'] = (int) ($data['cash'] * 1.10);
        return $data;
    },
];
```

**Side-effect / direct** (supports priority and multiple listeners per hook):

```php
use App\Facades\Hook;

Hook::register('alterCrimeRewards', function (array $data): array {
    $data['cash'] = (int) ($data['cash'] * 1.10);
    return $data;
}, priority: 50); // higher numbers run first; default is 10
```

### Priority rules

- `Hook::register()` accepts an optional `int $priority` (default: `10`).
- **Higher numbers execute first.** A listener at `100` runs before `50`, which runs before `10`.
- Listeners with equal priority run in registration order.
- Sorting is lazy — applied the first time a hook fires for each hook name.
- Declarative format always uses the default priority (`10`). Use direct registration when order matters.

### Exception isolation

If a listener throws, the exception is caught and logged. The **remaining listeners still execute** — one failing plugin cannot block another's hooks from firing.

See [docs/PLUGIN_HOOKS.md](docs/PLUGIN_HOOKS.md) for the full hook catalogue and usage examples.

---

**This contract is the foundation for all plugin development. Breaking it will break plugin compatibility.**
