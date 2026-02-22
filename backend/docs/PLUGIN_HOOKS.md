# Plugin Hook System

Hooks are the primary way plugins listen for, modify, or react to game events without importing Core classes directly.

---

## Registration API

Plugins register hook listeners inside their `hooks.php` file, which is loaded automatically by `AutoPluginHookLoader` on each request (only when the plugin is `enabled`).

Two registration formats are supported:

### 1. Declarative (recommended for simple hooks)

Return an associative array from `hooks.php`. The loader registers each key/value pair automatically on both `GameHooks` and `HookService`.

```php
// app/Plugins/MyPlugin/hooks.php
return [
    'economy.credit' => function (array $data): array {
        $data['amount'] *= 1.10; // +10% bonus
        return $data;
    },
    'OnCrimeCommit' => function (array $data): void {
        // fire-and-forget — no return value required
        \Log::info('Crime committed', ['player' => $data['player']->id]);
    },
];
```

### 2. Side-effect (direct registration, required for priority or multiple listeners per hook)

Call `Hook::register()` directly in the body of `hooks.php`.

```php
use App\Facades\Hook;

Hook::register('economy.credit', function (array $data): array {
    $data['amount'] *= 1.10;
    return $data;
}, priority: 50); // higher = runs first

Hook::register('economy.credit', function (array $data): array {
    $data['amount'] = max(0, $data['amount']); // safety floor — runs after the multiplier
    return $data;
}, priority: 10);
```

> Declarative format does **not** support priority — all declarative listeners run at the default priority (10). Use the side-effect format when ordering matters.

---

## Priority

Each listener has a numeric priority (default: `10`). **Higher numbers run first.**

```text
priority 100 → runs first
priority 50  → runs second
priority 10  → default (runs last among equals)
```

The sort is lazy — listeners are sorted the first time a hook fires, then cached. Listeners with equal priority run in registration order.

### When to use custom priority

- A plugin that **modifies** data should run before plugins that only **observe** it → use a higher priority (e.g. `50` or `100`).
- An audit/logging listener should run after all mutations → leave at the default priority (`10`).
- Two plugins that both modify the same field must agree on order → assign distinct priority values and document them.

---

## Transform vs. side-effect hooks

### Transform hook — listeners return modified data

The return value of each listener replaces the payload for the next listener in the chain. If a listener throws, the chain is aborted and the **original payload** is returned.

```php
// Core fires:
$result = GameHooks::fire('economy.credit', ['user' => $user, 'amount' => 500]);
// $result['amount'] may be modified by plugins
```

### Side-effect hook — listeners observe but do not modify

Return `null` or `void` and the payload is passed through unchanged. Use these for logging, notifications, analytics.

---

## Declaring hooks in plugin.json

List every hook your plugin **fires** or **listens to** in `plugin.json`. This is used by the admin panel and documentation tools.

```json
"hooks": {
    "OnCrimeCommit":       true,
    "alterCrimeRewards":   true,
    "economy.credit":      true
}
```

---

## Core hook catalogue

| Hook name | Type | Payload keys | Description |
| --- | --- | --- | --- |
| `economy.credit` | transform | `user`, `amount`, `reason` | Player receives cash |
| `economy.debit` | transform | `user`, `amount`, `reason` | Player spends cash |
| `action.before` | transform | `action`, `player`, `context` | Before any core action |
| `action.after` | side-effect | `action`, `player`, `result` | After any core action |
| `inventory.change` | side-effect | `player`, `item`, `change_type` | Inventory add/remove/use |
| `player.experience.gained` | transform | `player`, `amount`, `source` | XP awarded to a player |
| `OnCrimeCommit` | side-effect | `player`, `crime`, `result` | Crime attempt resolved |
| `alterCrimeRewards` | transform | `cash`, `experience`, `player` | Modify crime reward values |
| `OnItemBought` | side-effect | `player`, `item`, `quantity`, `cost` | Item purchased |
| `OnItemSold` | side-effect | `player`, `item`, `quantity`, `earnings` | Item sold |
| `OnItemEquipped` | side-effect | `player`, `item` | Item equipped |
| `OnItemUnequipped` | side-effect | `player`, `item` | Item unequipped |
| `OnItemUsed` | side-effect | `player`, `item` | Item used |
| `customMenus` | transform | `user` | Plugins contribute sidebar menu sections |
| `afterCombat` | side-effect | `attacker`, `defender`, `result` | PvP/NPC combat resolved |
| `alterCombatTarget` | transform | `target`, `attacker` | Modify combat target data |
| `modifyCombatPower` | transform | `power`, `player` | Modify combat power value |

---

## Rules

- **Never remove or rename an existing hook** — this breaks all listeners registered against it.
- **New hooks require a PR review** — add them to this catalogue and to `HookRegistry::defineCoreHooks()`.
- **Exception safety** — if your listener throws, the exception is caught, logged to Laravel's error log, and the **next listener still runs**. For transform hooks, the chain continues with the last successfully returned value. Listeners are isolated from each other — one bad plugin cannot block another's hooks from firing. Keep listeners lean and guard against unexpected input.
- **No I/O blocking** — listeners run synchronously on the request cycle. Use queued jobs for slow work.

---

## Example: full plugin hooks.php

```php
<?php
// app/Plugins/MyPlugin/hooks.php

use App\Facades\Hook;

// Self-register service binding
if (! app()->bound('myplugin')) {
    app()->singleton('myplugin', fn ($app) => $app->make(\App\Plugins\MyPlugin\Services\MyPluginService::class));
}

// Transform hook with high priority — runs before default listeners (default is 10)
Hook::register('alterCrimeRewards', function (array $data): array {
    if ($data['player']->hasActiveBoost('crime_bonus')) {
        $data['cash'] = (int) ($data['cash'] * 1.25);
    }
    return $data;
}, priority: 50);

// Side-effect observer — uses default priority, runs after high-priority transforms
Hook::register('OnCrimeCommit', function (array $data): void {
    \App\Plugins\MyPlugin\Models\CrimeLog::record($data['player'], $data['crime']);
}); // priority defaults to 10
```
