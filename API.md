# LaravelCP API Reference

Base URL: `/api/v1/` — All endpoints are prefixed with this path unless stated otherwise.

Content-Type: `application/json` — All requests and responses use JSON.

---

## Authentication

### Sanctum Token Auth (Game Client)

Authenticated endpoints require a `Bearer` token in the `Authorization` header:

```
Authorization: Bearer <plain-text-token>
```

Tokens are issued via `POST /login` and `POST /register`. Revoke with `POST /logout` or `POST /logout-all`.

### SPA Cookie Auth (Admin Panel)

The admin panel uses Sanctum's SPA authentication with cookie-based sessions. The admin SPA is served via the `GET /admin/{any?}` web route (a catch-all that returns `public/admin/index.html`). API requests from the admin SPA must include `Accept: application/json` and rely on session cookies rather than Bearer tokens.

### License Verification

Admin routes (`/api/v1/admin/*`) require the `verify.license` middleware in addition to `auth:sanctum` and `role:admin|moderator`. Requests will fail with 402 if no valid license is stored.

---

## Standard Response Envelope

### Success
```json
{
  "success": true,
  "message": "Operation completed",
  "data": "..."
}
```

Some endpoints return data directly at the top level without a `success` wrapper (e.g. `GET /user`, `GET /dashboard`).

### Error
```json
{
  "message": "The provided credentials are incorrect.",
  "errors": {
    "login": ["The provided credentials are incorrect."]
  }
}
```

Validation errors return HTTP 422 with an `errors` object keyed by field name.

### Pagination

Paginated responses use Laravel's default pagination structure:

```json
{
  "current_page": 1,
  "data": [ ... ],
  "first_page_url": "...",
  "from": 1,
  "last_page": 5,
  "last_page_url": "...",
  "links": [ ... ],
  "next_page_url": "...",
  "path": "...",
  "per_page": 25,
  "prev_page_url": null,
  "to": 25,
  "total": 125
}
```

---

## Common HTTP Status Codes

| Code | Description |
|------|-------------|
| 200 | OK — Successful request |
| 201 | Created — Resource created |
| 400 | Bad Request — Invalid input |
| 401 | Unauthenticated — Missing/invalid token |
| 402 | Payment Required — License not activated |
| 403 | Forbidden — Insufficient permissions |
| 404 | Not Found |
| 422 | Unprocessable Entity — Validation failure |
| 423 | Locked — Discussion is locked |
| 429 | Too Many Requests — Rate limit exceeded |
| 500 | Internal Server Error |

---

## API Reference

---

### Authentication

#### POST /register

Create a new user account.

**Request:**
```json
{
  "username": "gangster123",
  "email": "player@example.com",
  "password": "secret123",
  "password_confirmation": "secret123"
}
```

**Response (201):**
```json
{
  "user": {
    "id": 1,
    "name": "gangster123",
    "username": "gangster123",
    "email": "player@example.com",
    "level": 1,
    "experience": 0,
    "cash": 1000,
    "bank": 0,
    "energy": 100,
    "max_energy": 100,
    "health": 100,
    "max_health": 100,
    "respect": 0,
    "bullets": 50,
    "rank": "Thug",
    "location": "Detroit",
    "roles": ["user"],
    "two_factor_enabled": false,
    "created_at": "2025-01-01T00:00:00.000000Z"
  },
  "token": "1|abc123..."
}
```

Rate limited: 10 requests per minute.

#### POST /login

Authenticate with email or username.

**Request:**
```json
{
  "login": "player@example.com",
  "password": "secret123"
}
```

**Response (200):** Same format as register.

If 2FA is enabled, returns a challenge token instead:
```json
{
  "two_factor_required": true,
  "challenge_token": "64-char-hex-string",
  "user": { ... }
}
```

Rate limited: 10 requests per minute.

#### POST /logout

Revoke current token.

**Headers:** `Authorization: Bearer <token>`

**Response (200):**
```json
{ "message": "Logged out successfully" }
```

#### POST /logout-all

Revoke all tokens for the authenticated user.

**Headers:** `Authorization: Bearer <token>`

**Response (200):**
```json
{ "message": "Logged out from all devices" }
```

#### GET /user

Get the authenticated user's profile with roles, permissions, and linked OAuth providers.

**Headers:** `Authorization: Bearer <token>`

**Response (200):**
```json
{
  "id": 1,
  "name": "gangster123",
  "username": "gangster123",
  "email": "player@example.com",
  "level": 5,
  "experience": 2500,
  "cash": 15000,
  "bank": 5000,
  "energy": 85,
  "max_energy": 100,
  "health": 100,
  "max_health": 100,
  "respect": 120,
  "bullets": 50,
  "rank": "Hustler",
  "location": "Detroit",
  "roles": ["user"],
  "permissions": [],
  "oauth_providers": ["discord"],
  "two_factor_enabled": true,
  "created_at": "2025-01-01T00:00:00.000000Z",
  "updated_at": "2025-06-15T12:00:00.000000Z"
}
```

#### POST /user/change-password

Change the authenticated user's password.

**Headers:** `Authorization: Bearer <token>`

**Request:**
```json
{
  "current_password": "old-password",
  "new_password": "new-secret-123",
  "new_password_confirmation": "new-secret-123"
}
```

For forced password changes, include `"force_change": true` and omit `current_password`.

**Response (200):**
```json
{ "message": "Password changed successfully" }
```

#### POST /user/username

Update the authenticated user's username.

**Headers:** `Authorization: Bearer <token>`

**Request:**
```json
{
  "username": "new-name"
}
```

**Response (200):**
```json
{
  "message": "Username updated successfully",
  "user": { ... }
}
```

---

### Password Reset

#### POST /forgot-password

Send a password reset link to the user's email.

**Request:**
```json
{ "email": "player@example.com" }
```

**Response (200):**
```json
{
  "success": true,
  "message": "If an account with that email exists, you will receive a password reset link shortly."
}
```

Rate limited: 10 requests per minute.

#### POST /validate-reset-token

Validate a password reset token.

**Request:**
```json
{
  "token": "64-char-random-string",
  "email": "player@example.com"
}
```

**Response (200):**
```json
{
  "valid": true,
  "message": "Token is valid."
}
```

#### POST /reset-password

Reset the password using a valid token.

**Request:**
```json
{
  "token": "64-char-random-string",
  "email": "player@example.com",
  "password": "new-password-123",
  "password_confirmation": "new-password-123"
}
```

**Response (200):**
```json
{
  "success": true,
  "message": "Password has been reset successfully."
}
```

---

### Two-Factor Authentication (2FA)

#### GET /2fa/status

Get 2FA status for the authenticated user.

**Headers:** `Authorization: Bearer <token>`

**Response (200):**
```json
{
  "enabled": false,
  "confirmed_at": null
}
```

#### POST /2fa/setup

Generate a new TOTP secret and QR code.

**Headers:** `Authorization: Bearer <token>`

**Response (200):**
```json
{
  "secret": "JBSWY3DPEHPK3PXP",
  "qr_code": "data:image/svg+xml;base64,...",
  "qr_code_url": "otpauth://totp/LaravelCP:player@example.com?secret=..."
}
```

#### POST /2fa/confirm

Confirm and enable 2FA by verifying the TOTP code.

**Headers:** `Authorization: Bearer <token>`

**Request:**
```json
{ "code": "123456" }
```

**Response (200):**
```json
{
  "message": "Two-factor authentication has been enabled.",
  "recovery_codes": ["XXXX-XXXX-XXXX", ...]
}
```

#### POST /2fa/disable

Disable 2FA. Requires current password.

**Headers:** `Authorization: Bearer <token>`

**Request:**
```json
{ "password": "current-password" }
```

**Response (200):**
```json
{ "message": "Two-factor authentication has been disabled." }
```

#### POST /2fa/recovery-codes

Get current recovery codes. Requires current password.

**Headers:** `Authorization: Bearer <token>`

**Request:**
```json
{ "password": "current-password" }
```

**Response (200):**
```json
{
  "recovery_codes": ["XXXX-XXXX-XXXX", ...]
}
```

#### POST /2fa/regenerate-recovery-codes

Regenerate recovery codes. Invalidates old ones. Requires current password.

**Headers:** `Authorization: Bearer <token>`

**Request:**
```json
{ "password": "current-password" }
```

**Response (200):**
```json
{
  "recovery_codes": ["NEW1-NEW2-NEW3", ...]
}
```

#### POST /2fa/verify

Verify a 2FA code during login (public, no auth required).

Rate limited: 10 requests per minute.

**Request:**
```json
{
  "challenge_token": "64-char-hex-string",
  "code": "123456"
}
```

**Response (200):**
```json
{
  "user": { ... },
  "token": "1|abc123..."
}
```

---

### OAuth Authentication

Supported providers: `discord`, `google`, `github`, `twitter`, `facebook`

#### GET /oauth/providers

List available and configured OAuth providers.

**Response (200):**
```json
{
  "providers": ["discord", "google", "github"]
}
```

Only providers with credentials configured in settings are returned.

#### GET /oauth/{provider}/redirect

Get the OAuth provider's authorization URL.

**Response (200):**
```json
{
  "redirect_url": "https://discord.com/api/oauth2/authorize?..."
}
```

#### GET /oauth/{provider}/callback

Handle OAuth callback from provider. Accepts query parameters as returned by the provider.

**Behavior:**
- If the user exists (linked provider or matching email), returns a token.
- If the user is new and the provider is Discord, returns `pending_token` for username completion.
- For browser redirects (non-XHR), redirects to the frontend SPA with token in query string.

**XHR Response (200) — Existing user:**
```json
{
  "user": { ... },
  "token": "1|abc123...",
  "is_new_user": false
}
```

**XHR Response (200) — New user (Discord):**
```json
{
  "is_new_user": true,
  "pending_token": "oauth_pending_abc123...",
  "socialite": {
    "provider": "discord",
    "provider_id": "12345",
    "email": "user@example.com",
    "name": "User",
    "nickname": "user#1234",
    "avatar": "https://cdn.discordapp.com/..."
  }
}
```

#### POST /oauth/{provider}/complete

Complete registration by choosing a username after a pending OAuth callback.

**Request:**
```json
{
  "pending_token": "oauth_pending_abc123...",
  "username": "chosen-username"
}
```

**Response (200):**
```json
{
  "user": { ... },
  "token": "1|abc123..."
}
```

#### GET /oauth/linked

Get OAuth providers linked to the authenticated user.

**Headers:** `Authorization: Bearer <token>`

**Response (200):**
```json
{
  "providers": [
    {
      "provider": "discord",
      "nickname": "user#1234",
      "avatar": "https://cdn.discordapp.com/...",
      "linked_at": "2025-01-01T00:00:00.000000Z"
    }
  ]
}
```

#### GET /oauth/{provider}/link

Link an OAuth provider to the authenticated user. Redirects to the provider's authorization page.

**Headers:** `Authorization: Bearer <token>`

Returns a redirect to the provider's OAuth URL.

#### DELETE /oauth/{provider}/unlink

Unlink an OAuth provider from the authenticated user.

**Headers:** `Authorization: Bearer <token>`

**Response (200):**
```json
{
  "message": "Discord account unlinked successfully.",
  "providers": [ ... ]
}
```

Returns 400 if unlinking would leave the user without any login method.

---

### User Profile

#### GET /player/{id}

Get a player's public profile.

**Headers:** `Authorization: Bearer <token>`

**Response (200):**
```json
{
  "player": {
    "id": 1,
    "username": "gangster123",
    "level": 5,
    "experience": 2500,
    "rank_title": "Hustler",
    "respect": 120,
    "cash": 15000,
    "bank": 5000,
    "networth": 20000,
    "health": 100,
    "max_health": 100,
    "energy": 85,
    "max_energy": 100,
    "bullets": 50,
    "location": "Detroit",
    "created_at": "2025-01-01T00:00:00.000000Z",
    "last_active": "2025-06-15T12:00:00.000000Z"
  },
  "stats": {
    "total_attempts": 42,
    "successful": 38,
    "failed": 4,
    "success_rate": 90.48,
    "total_earnings": 50000,
    "total_respect_earned": 200
  },
  "recent_crimes": [
    {
      "id": 1,
      "crime_name": "Armed Robbery",
      "success": true,
      "cash_earned": 5000,
      "time_ago": "2 hours ago"
    }
  ],
  "is_own_profile": true
}
```

Crime stats are only included when the Crimes plugin is loaded.

---

### Player Statistics

#### GET /stats

Get the authenticated user's full statistics.

**Headers:** `Authorization: Bearer <token>`

**Response (200):**
```json
{
  "crimes_committed": 42,
  "battles_won": 15,
  "battles_lost": 3,
  "win_rate": 83.33,
  "total_kills": 0,
  "jail_time": 0,
  "times_jailed": 2,
  "bounties_claimed": 1,
  "bounties_placed": 0,
  "crimes": {
    "total_attempts": 42,
    "successful": 38,
    "failed": 4
  },
  "combat": {
    "wins": 15,
    "losses": 3,
    "win_rate": 83.33
  },
  "economy": {
    "current_cash": 15000,
    "bank_balance": 5000,
    "total_earned": 100000
  },
  "leaderboard_position": {
    "rank": 42,
    "total": 500
  }
}
```

#### GET /stats/player/{userId}

Get a specific player's public statistics.

**Headers:** `Authorization: Bearer <token>`

**Response (200):**
```json
{
  "success": true,
  "player": {
    "id": 1,
    "username": "gangster123",
    "level": 5,
    "rank": "Hustler"
  },
  "stats": { ... },
  "leaderboard_position": { "rank": 42, "total": 500 }
}
```

Sensitive economy data (current_cash, bank_balance) is excluded for other players.

#### POST /stats/refresh

Clear cached stats and force recalculation.

**Headers:** `Authorization: Bearer <token>`

**Response (200):**
```json
{
  "success": true,
  "message": "Statistics refreshed successfully"
}
```

---

### Dashboard

#### GET /dashboard

Main game dashboard — returns player data, enabled modules, navigation, timers, and unread count.

**Headers:** `Authorization: Bearer <token>`

**Response (200):**
```json
{
  "player": {
    "id": 1,
    "username": "gangster123",
    "level": 5,
    "energy": 85,
    "max_energy": 100,
    "health": 100,
    "max_health": 100,
    "cash": 15000,
    "bank": 5000,
    "respect": 120,
    "bullets": 50,
    "exp_progress": 75
  },
  "modules": [
    {
      "id": 1,
      "name": "crimes",
      "display_name": "Crimes",
      "description": "Commit crimes to earn cash",
      "icon": "icon-crime",
      "route_name": "crimes.index",
      "required_level": 1,
      "locked": false,
      "order": 1
    }
  ],
  "navigationItems": [
    { "label": "Home", "route": "dashboard", "icon": "HomeIcon" }
  ],
  "dailyReward": {
    "available": true,
    "current_streak": 3,
    "reward": 500
  },
  "activeTimers": [
    { "type": "crime", "expires_at": "2025-06-15T12:05:00.000000Z", "remaining_seconds": 120 }
  ],
  "unreadNotifications": 3
}
```

---

### Notifications

#### GET /notifications

Get all notifications for the authenticated user (up to 100).

**Headers:** `Authorization: Bearer <token>`

**Response (200):**
```json
{
  "notifications": [
    {
      "id": 1,
      "type": "crime_result",
      "title": "Crime Committed",
      "message": "You successfully committed Armed Robbery and earned $5,000",
      "icon": "💰",
      "link": "/crimes",
      "data": { "crime_id": 1, "earnings": 5000 },
      "is_read": false,
      "time_ago": "2 hours ago",
      "created_at": "2025-06-15T10:00:00.000000Z"
    }
  ],
  "unread_count": 3
}
```

#### GET /notifications/recent

Get recent notifications (up to 10) for dropdown display.

**Headers:** `Authorization: Bearer <token>`

**Response (200):** Same structure as index, but limited to 10 items.

#### GET /notifications/unread-count

Get unread notification count for navbar badge.

**Headers:** `Authorization: Bearer <token>`

**Response (200):**
```json
{ "count": 3 }
```

#### POST /notifications/{id}/read

Mark a notification as read.

**Headers:** `Authorization: Bearer <token>`

**Response (200):**
```json
{ "success": true, "message": "Notification marked as read" }
```

#### POST /notifications/mark-all-read

Mark all notifications as read.

**Headers:** `Authorization: Bearer <token>`

**Response (200):**
```json
{ "success": true, "count": 3, "message": "Marked 3 notifications as read" }
```

#### DELETE /notifications/{id}

Delete a specific notification.

**Headers:** `Authorization: Bearer <token>`

**Response (200):**
```json
{ "success": true, "message": "Notification deleted" }
```

#### DELETE /notifications/read/clear

Delete all read notifications.

**Headers:** `Authorization: Bearer <token>`

**Response (200):**
```json
{ "success": true, "count": 5, "message": "Deleted 5 read notifications" }
```

---

### Activity

#### GET /activity

Get the authenticated user's activity history.

Alias: `GET /activity/my-activity`

**Headers:** `Authorization: Bearer <token>`

**Query Parameters:**
- `limit` (int, default: 50) — Max entries to return

**Response (200):**
```json
{
  "success": true,
  "activity": [
    {
      "id": 1,
      "type": "crime_attempt",
      "description": "Committed Armed Robbery",
      "metadata": { "crime_name": "Armed Robbery", "earnings": 5000 },
      "created_at": "2025-06-15T10:00:00.000000Z"
    }
  ]
}
```

---

### Community

#### GET /community/discussion-categories

List all discussion categories.

**Response (200):**
```json
{
  "data": [
    {
      "id": 1,
      "name": "General",
      "slug": "general",
      "description": "General game discussion",
      "icon": "💬",
      "color": "#3b82f6",
      "count": 25
    }
  ]
}
```

#### GET /community/discussions

List published discussions with search and category filter.

**Query Parameters:**
- `search` (string) — Search in title, excerpt, and body
- `category` (string) — Filter by category slug
- `limit` (int, 1–50, default: 25)

**Response (200):**
```json
{
  "data": [
    {
      "id": 1,
      "title": "Welcome to the Game",
      "slug": "welcome-to-the-game",
      "tag": "Announcement",
      "excerpt": "Welcome to our community...",
      "status": "published",
      "is_pinned": true,
      "is_locked": false,
      "replies": 5,
      "views": 150,
      "href": "/discussions/welcome-to-the-game",
      "category": {
        "id": 1,
        "name": "Announcements",
        "slug": "announcements",
        "icon": "📢",
        "color": "#ef4444"
      },
      "author": {
        "id": 1,
        "name": "Admin",
        "username": "admin",
        "initial": "A"
      },
      "created_at": "2025-01-01T00:00:00.000000Z",
      "time_ago": "5 months ago",
      "last_reply_at": "2025-06-14T15:00:00.000000Z",
      "last_activity": "1 day ago"
    }
  ],
  "meta": {
    "categories": [ ... ],
    "trending": [ ... ]
  }
}
```

#### GET /community/discussions/{discussion}

Get a single discussion by slug or ID with replies.

**Response (200):**
Same format as index, but also includes `reply_items` array:

```json
{
  "data": {
    "id": 1,
    "title": "Welcome to the Game",
    "body": "Full body text...",
    "reply_items": [
      {
        "id": 1,
        "body": "Great game!",
        "votes": 3,
        "is_solution": false,
        "author": {
          "id": 2,
          "name": "Player1",
          "username": "player1",
          "initial": "P"
        },
        "created_at": "2025-01-02T00:00:00.000000Z",
        "time_ago": "5 months ago"
      }
    ]
  }
}
```

#### POST /community/discussions

Create a new discussion.

**Headers:** `Authorization: Bearer <token>`

**Request:**
```json
{
  "title": "My Discussion",
  "body": "Discussion content here...",
  "category_id": 1,
  "tag": "Question"
}
```

`category_slug` can be used instead of `category_id`.

**Response (201):**
```json
{ "data": { ... } }
```

#### POST /community/discussions/{discussion}/replies

Reply to a discussion.

**Headers:** `Authorization: Bearer <token>`

**Request:**
```json
{
  "body": "My reply content...",
  "attachment_name": "optional-filename.txt"
}
```

**Response (201):**
```json
{ "data": { ... } }
```

Returns 423 if the discussion is locked.

---

### License

#### GET /license/status

Get current license status (public, no auth).

**Response (200) — Licensed:**
```json
{
  "licensed": true,
  "can_generate": false,
  "key": "LICEN*****1234",
  "tier": "standard",
  "customer": "Customer Name",
  "email": "customer@example.com",
  "domain": "*",
  "issued": "2025-01-01",
  "expires": "never",
  "max_users": "unlimited",
  "plugins": "*"
}
```

**Response (200) — No license:**
```json
{
  "licensed": false,
  "can_generate": true,
  "message": "No license key found."
}
```

#### POST /license/activate

Activate a license key (public, no auth).

**Request:**
```json
{
  "license_key": "XXXX-XXXX-XXXX-XXXX"
}
```

**Response (200):**
```json
{
  "success": true,
  "message": "License activated successfully.",
  "tier": "standard",
  "customer": "Customer Name",
  "expires": "never"
}
```

---

### Plugins (Public)

#### GET /plugins/enabled

Get all enabled plugins for frontend navigation. Public — no auth required.

**Response (200):**
```json
{
  "success": true,
  "plugins": [
    {
      "slug": "crimes",
      "name": "Crimes",
      "version": "1.0.0"
    }
  ],
  "navigation": [
    { "label": "Crimes", "route": "crimes.index", "icon": "icon-crime" }
  ],
  "routes": {
    "crimes.index": "/crimes"
  }
}
```

---

### WebSocket

#### POST /ws/auth

Authorize a private or presence channel subscription.

**Headers:** `Authorization: Bearer <token>`

**Request:**
```json
{
  "channel_name": "private-user.1"
}
```

**Response (200) — Private channel:**
```json
{ "auth": true }
```

**Response (200) — Presence channel:**
```json
{
  "user_id": 1,
  "user_info": {
    "id": 1,
    "username": "gangster123",
    "avatar": null
  }
}
```

**Response (403):**
```json
{ "message": "Unauthorized" }
```

#### POST /ws/poll

Long-polling fallback for clients without WebSocket support.

**Headers:** `Authorization: Bearer <token>`

**Request:**
```json
{
  "channels": ["private-user.1", "presence-global"],
  "since": "2025-06-15T12:00:00.000000Z"
}
```

**Response (200):**
```json
{
  "messages": [
    {
      "channel": "private-user.1",
      "event": "notification",
      "data": { ... },
      "timestamp": "2025-06-15T12:01:00.000000Z"
    }
  ],
  "timestamp": "2025-06-15T12:01:30.000000Z"
}
```

#### GET /ws/online-count

Get the count of currently online users.

**Headers:** `Authorization: Bearer <token>`

**Response (200):**
```json
{ "count": 42 }
```

#### POST /ws/heartbeat

Send a heartbeat to maintain online status.

**Headers:** `Authorization: Bearer <token>`

**Response (200):**
```json
{ "status": "ok", "online_count": 42 }
```

#### GET /ws/presence/{channel}

Get members of a presence channel.

**Headers:** `Authorization: Bearer <token>`

**Response (200):**
```json
{
  "members": [
    { "id": 1, "username": "gangster123" },
    { "id": 2, "username": "player2" }
  ]
}
```

---

### Text Formatting

#### POST /format/preview

Preview formatted BBCode/emoji text.

**Headers:** `Authorization: Bearer <token>`

**Request:**
```json
{
  "text": "[b]Bold[/b] and :smile:",
  "bbcode": true,
  "emoji": true
}
```

**Response (200):**
```json
{
  "original": "[b]Bold[/b] and :smile:",
  "formatted": "<b>Bold</b> and 😄"
}
```

#### GET /format/bbcodes

Get available BBCode tags.

**Headers:** `Authorization: Bearer <token>`

**Response (200):**
```json
{
  "bbcodes": {
    "b": { "description": "Bold text", "example": "[b]text[/b]" },
    "i": { "description": "Italic text", "example": "[i]text[/i]" }
  }
}
```

#### GET /format/emojis

Get popular emoji shortcodes grouped by category.

**Headers:** `Authorization: Bearer <token>`

**Response (200):**
```json
{
  "emojis": {
    "Smileys": [":smile:", ":joy:", ":heart_eyes:"],
    "Nature": [":sunny:", ":rainbow:"]
  }
}
```

#### POST /format/plain

Strip formatting and return plain text (for notifications, etc.).

**Headers:** `Authorization: Bearer <token>`

**Request:**
```json
{ "text": "[b]Hello[/b] :smile:" }
```

**Response (200):**
```json
{ "plain": "Hello :smile:" }
```

#### GET /format/emoji/search

Search emoji shortcodes by query.

**Headers:** `Authorization: Bearer <token>`

**Query Parameters:**
- `q` (string, min 2 chars) — Search query

**Response (200):**
```json
{
  "results": [
    { "shortcode": ":smile:", "category": "Smileys", "unicode": "😄" }
  ]
}
```

---

### Emoji (Legacy)

#### GET /emojis

Get all available emojis organized by category.

**Headers:** `Authorization: Bearer <token>`

**Response (200):**
```json
{
  "categories": { "Smileys": ["😄", "😊"], "Nature": ["🌲", "🌺"] },
  "quick_reactions": ["😄", "👍", "❤️", "😂"]
}
```

#### GET /emojis/quick-reactions

Get quick-reaction emojis only.

**Headers:** `Authorization: Bearer <token>`

**Response (200):**
```json
["😄", "👍", "❤️", "😂"]
```

#### GET /emojis/search

Search emojis by category name.

**Headers:** `Authorization: Bearer <token>`

**Query Parameters:**
- `q` (string) — Search query

**Response (200):**
```json
["😄", "😊"]
```

---

### Error Logging (Frontend)

#### POST /log-frontend-error

Log a frontend JavaScript error.

Rate limited: 30 requests per minute.

**Request:**
```json
{
  "message": "Cannot read property 'x' of undefined",
  "source": "app.js",
  "line": 42,
  "column": 10,
  "stack": "Error: ...",
  "url": "https://example.com/dashboard",
  "severity": "error",
  "app_source": "openpbbg"
}
```

**Response (200):**
```json
{ "success": true, "message": "Error logged successfully" }
```

#### POST /log-api-error

Log an API call failure from the frontend.

Rate limited: 30 requests per minute.

**Request:**
```json
{
  "endpoint": "/api/v1/crimes",
  "method": "GET",
  "status_code": 500,
  "error_message": "Server error",
  "request_data": {},
  "response_data": {}
}
```

#### POST /log-vue-error

Log a Vue component error.

Rate limited: 30 requests per minute.

**Request:**
```json
{
  "error": "TypeError: ...",
  "component": "Dashboard",
  "hook": "mounted",
  "info": "during navigation"
}
```

---

### Admin Endpoints

All admin endpoints are under `/api/v1/admin/` and require:
- Authentication: `auth:sanctum`
- Role: `admin|moderator`
- Valid license: `verify.license`

---

#### Admin Dashboard

##### GET /admin/stats

Get admin dashboard statistics.

**Query Parameters:**
- `days` (int, default: 7) — Lookback period

**Response (200):**
```json
{
  "totalUsers": 500,
  "newUsers": { "count": 15, "change": 10.5 },
  "activeUsers": 120,
  "activePercentage": 24.0,
  "totalMoney": 5000000,
  "activityChart": {
    "labels": ["Mon", "Tue", "Wed", "Thu", "Fri", "Sat", "Sun"],
    "datasets": [
      { "label": "Active Users", "data": [50, 60, 55, 70, 80, 65, 45] },
      { "label": "New Signups", "data": [5, 8, 3, 7, 12, 10, 6] }
    ]
  },
  "retention": [
    { "week": "This Week", "users": 50, "day1": 80, "day7": 45 }
  ],
  "hourlyActivity": [
    { "hour": 0, "value": 25 }
  ],
  "topActivities": [
    { "name": "Crime Attempt", "count": 500, "percentage": 100 }
  ],
  "levelDistribution": [
    { "range": "1-10", "percentage": 45.0 }
  ],
  "economy": { "total_cash": 3000000, "total_bank": 2000000, "total": 5000000 },
  "widgets": []
}
```

---

#### Admin License Management

##### GET /admin/license/status

Get current license status and details.

Response: Same as public `GET /license/status`.

##### POST /admin/license/activate

Activate a license key.

**Request:**
```json
{ "license_key": "XXXX-XXXX-XXXX-XXXX" }
```

##### POST /admin/license/generate

Generate a new license key (requires private key on server).

**Request:**
```json
{
  "domain": "example.com",
  "tier": "standard",
  "customer": "Customer Name",
  "email": "customer@example.com",
  "expires": "2026-01-01",
  "max_users": 100,
  "plugins": "all"
}
```

##### DELETE /admin/license/deactivate

Remove the current license.

##### GET /admin/license/keys

List all generated license keys (paginated).

**Query Parameters:**
- `status` (activated|pending|revoked)
- `search` (string)

##### PUT /admin/license/keys/{id}

Update a license key's notes.

**Request:**
```json
{ "notes": "Customer notes here" }
```

##### POST /admin/license/keys/{id}/revoke

Revoke a license key.

---

#### Admin Plugin Management

##### GET /admin/plugins

List all plugins (installed, staging, disabled).

**Query Parameters:**
- `type` — Set to `theme` to list themes

##### POST /admin/plugins/upload

Upload a plugin/theme ZIP file.

**Request:** `multipart/form-data`
- `file` (file, .zip)
- `type` (plugin|theme)

##### POST /admin/plugins/create

Create a new plugin scaffold.

**Request:**
```json
{ "slug": "my-plugin", "name": "My Plugin" }
```

##### POST /admin/plugins/{slug}/install

Install a plugin from staging.

##### DELETE /admin/plugins/{slug}

Uninstall a plugin.

##### PUT /admin/plugins/{slug}/enable

Enable a plugin.

##### PUT /admin/plugins/{slug}/disable

Disable a plugin.

##### PUT /admin/plugins/{slug}/reactivate

Reactivate a disabled plugin.

##### DELETE /admin/plugins/{slug}/staging

Remove a staging directory.

##### POST /admin/plugins/{slug}/install-theme

Install a theme.

##### PUT /admin/plugins/{slug}/activate-theme

Activate a theme (disables others).

##### GET /admin/plugins/themes

List all themes.

---

#### Admin User Management

##### GET /admin/users

List users with filters and pagination.

**Query Parameters:**
- `search` (string) — Search username, email, name
- `rank_id` (int) — Filter by rank
- `role` (string) — Filter by role name
- `status` (banned|active)
- `sort_by` (string, default: created_at)
- `sort_dir` (asc|desc)
- `per_page` (int, default: 25)

##### GET /admin/users/statistics

Get user statistics overview.

**Response (200):**
```json
{
  "total_users": 500,
  "active_today": 80,
  "active_week": 250,
  "banned": 5,
  "new_today": 3,
  "new_week": 20
}
```

##### POST /admin/users

Create a new user.

**Request:**
```json
{
  "username": "newplayer",
  "email": "new@example.com",
  "name": "New Player",
  "password": "secret123",
  "rank_id": 1,
  "location_id": 1
}
```

##### GET /admin/users/{user}

Get single user with profile and roles.

##### PATCH /admin/users/{user}

Update user identity and/or game stats.

**Request:** Any combination of:
```json
{
  "username": "new-name",
  "email": "new@example.com",
  "password": "new-secret",
  "cash": 50000,
  "level": 10,
  "energy": 100,
  "health": 100,
  "rank_id": 2
}
```

Game stat changes require the `manageGameStats` permission.

##### DELETE /admin/users/{user}

Delete a user.

##### POST /admin/users/{user}/ban

Ban a user.

**Request:**
```json
{
  "reason": "Rule violation",
  "duration": "7days",
  "notes": "Additional notes"
}
```

Duration options: `1day`, `3days`, `7days`, `30days`, `permanent`.

##### POST /admin/users/{user}/unban

Unban a user.

---

#### Admin Roles & Permissions

##### GET /admin/roles

List all roles with their permissions.

##### POST /admin/roles

Create a new role.

**Request:**
```json
{
  "name": "moderator",
  "permissions": ["manage users", "view reports"]
}
```

##### PATCH /admin/roles/{id}

Update a role's name and/or permissions.

##### DELETE /admin/roles/{id}

Delete a role.

##### GET /admin/permissions

List all permissions grouped by module.

##### POST /admin/users/{id}/roles

Assign a role to a user.

**Request:**
```json
{ "role": "moderator" }
```

##### DELETE /admin/users/{id}/roles

Remove a role from a user.

**Request:**
```json
{ "role": "moderator" }
```

---

#### Admin Settings

##### GET /admin/settings

Get all settings as a flat key-value object.

##### GET /admin/settings/all

Get all settings with plugin-defined defaults and groups.

**Response (200):**
```json
{
  "settings": {
    "game_name": "Gangster Legends",
    "registration_enabled": true
  },
  "groups": {
    "general": {
      "label": "General",
      "icon": "Cog6ToothIcon",
      "order": 0,
      "plugin_name": "Core",
      "plugin_slug": "core",
      "settings": {
        "game_name": { "type": "text", "label": "Game Name", "default": "Gangster Legends" }
      }
    }
  }
}
```

##### GET /admin/settings/plugin-schema

Get plugin-defined admin settings schemas.

##### POST /admin/settings

Create or update settings (handles both single setting and bulk).

**Single setting:**
```json
{ "key": "game_name", "value": "My Game", "category": "general", "type": "text" }
```

**Bulk:**
```json
{ "game_name": "My Game", "registration_enabled": true }
```

##### PATCH /admin/settings

Update settings (bulk).

**Request:**
```json
{ "settings": [{ "key": "game_name", "value": "My Game" }] }
```

Or flat object:
```json
{ "game_name": "My Game", "registration_enabled": false }
```

##### GET /admin/settings/security

Get security-specific settings (2FA config, OAuth status, password policies).

##### POST /admin/settings/oauth/{provider}

Save OAuth provider credentials.

**Request:**
```json
{
  "client_id": "abc123",
  "client_secret": "secret456",
  "enabled": true
}
```

##### GET /admin/settings/{key}

Get a specific setting.

##### DELETE /admin/settings/{key}

Delete a setting.

---

#### Admin Webhooks

##### GET /admin/webhooks

List all webhooks (paginated).

##### GET /admin/webhooks/events

Get available webhook event types.

##### POST /admin/webhooks

Create a webhook.

**Request:**
```json
{
  "name": "Discord Log",
  "url": "https://discord.com/api/webhooks/...",
  "events": ["player.login", "crime.committed"],
  "headers": { "X-Custom": "value" },
  "is_active": true,
  "retry_count": 3
}
```

##### GET /admin/webhooks/{id}

Get webhook details with recent deliveries.

##### PATCH /admin/webhooks/{id}

Update a webhook.

##### DELETE /admin/webhooks/{id}

Delete a webhook and its delivery history.

##### POST /admin/webhooks/{id}/toggle

Toggle webhook active/inactive.

##### POST /admin/webhooks/{id}/test

Send a test delivery.

##### GET /admin/webhooks/{id}/deliveries

Get delivery history (paginated).

##### POST /admin/webhooks/{id}/deliveries/{deliveryId}/retry

Retry a failed delivery.

##### POST /admin/webhooks/{id}/regenerate-secret

Regenerate the webhook signing secret.

---

#### Admin Email Settings

##### GET /admin/email/settings

Get email configuration settings.

##### POST /admin/email/settings

Update email settings.

##### POST /admin/email/settings/test

Test email settings by sending a test email.

##### POST /admin/email/send

Send a manual email to a user.

##### GET /admin/email/templates

List all email templates.

##### POST /admin/email/templates

Create a new email template.

##### POST /admin/email/templates/seed-defaults

Seed default email templates.

##### GET /admin/email/templates/{id}

Get a specific email template.

##### PATCH /admin/email/templates/{id}

Update an email template.

##### DELETE /admin/email/templates/{id}

Delete an email template.

##### POST /admin/email/templates/{id}/preview

Preview an email template.

##### POST /admin/email/templates/{id}/test

Send a test email from a template.

---

#### Admin Community Moderation

##### GET /admin/community/discussions

List all discussions (including non-published) for moderation.

##### PATCH /admin/community/discussions/{discussion}

Update a discussion (e.g. change status, lock/unlock).

##### DELETE /admin/community/discussions/{discussion}

Delete a discussion.

---

#### Admin Configurable Type Tables

These are standard `apiResource` routes with typical RESTful patterns:

| Resource | Endpoint | Controller |
|----------|----------|------------|
| Item Rarities | `/admin/item-rarities` | `ItemRarityController` |
| Property Types | `/admin/property-types` | `PropertyTypeController` |
| Announcement Types | `/admin/announcement-types` | `AnnouncementTypeController` |
| Crime Difficulties | `/admin/crime-difficulties` | `CrimeDifficultyController` |
| Casino Game Types | `/admin/casino-game-types` | `CasinoGameTypeController` |
| Company Industries | `/admin/company-industries` | `CompanyIndustryController` |
| Stock Sectors | `/admin/stock-sectors` | `StockSectorController` |
| Course Skills | `/admin/course-skills` | `CourseSkillController` |
| Course Difficulties | `/admin/course-difficulties` | `CourseDifficultyController` |
| Achievement Stats | `/admin/achievement-stats` | `AchievementStatController` |
| Mission Frequencies | `/admin/mission-frequencies` | `MissionFrequencyController` |
| Mission Objective Types | `/admin/mission-objective-types` | `MissionObjectiveTypeController` |
| Bounty Statuses | `/admin/bounty-statuses` | `BountyStatusController` |
| Lottery Statuses | `/admin/lottery-statuses` | `LotteryStatusController` |
| Item Effect Types | `/admin/item-effect-types` | `ItemEffectTypeController` |
| Item Modifier Types | `/admin/item-modifier-types` | `ItemModifierTypeController` |

Each supports: `GET /{resource}`, `POST /{resource}`, `GET /{resource}/{id}`, `PATCH /{resource}/{id}`, `DELETE /{resource}/{id}`.

---

#### Admin Error Logs

##### GET /admin/error-logs

List error logs with filters and pagination.

**Query Parameters:**
- `resolved` (bool)
- `type` (string) — Filter by error type
- `level` (string) — error, warning, critical, etc.
- `source` (string) — backend, admin, openpbbg, laravel-log
- `dateRange` (today|yesterday|week|month)
- `search` (string)
- `per_page` (int, default: 50)

##### GET /admin/error-logs/statistics

Get error statistics overview.

##### GET /admin/error-logs/laravel-log

Parse and return recent Laravel log entries.

##### POST /admin/error-logs/laravel-log/sync

Sync Laravel log file entries to the database.

##### DELETE /admin/error-logs/laravel-log/clear

Clear the Laravel log file.

##### DELETE /admin/error-logs/clear

Clear all error logs (truncate table).

##### GET /admin/error-logs/{id}

Get a specific error log with user details.

##### PATCH /admin/error-logs/{id}/resolve

Mark an error as resolved.

##### PATCH /admin/error-logs/{id}/unresolve

Mark an error as unresolved.

##### DELETE /admin/error-logs/{id}

Delete a specific error log.

##### POST /admin/error-logs/bulk-resolve

Bulk-resolve errors by IDs.

**Request:**
```json
{ "ids": [1, 2, 3] }
```

##### POST /admin/error-logs/bulk-delete

Bulk-delete errors by IDs.

##### DELETE /admin/error-logs/resolved/all

Delete all resolved errors.

##### DELETE /admin/error-logs/old

Delete errors older than N days.

**Query Parameters:**
- `days` (int, default: 30)

---

#### Admin Notifications

##### GET /admin/notifications

List admin notifications (paginated).

**Query Parameters:**
- `type` (string) — Filter by type
- `status` (unread|read)
- `priority` (low|normal|high|urgent)
- `per_page` (int, default: 20)

##### GET /admin/notifications/recent

Get recent notifications for dropdown (limit 10).

##### GET /admin/notifications/unread-count

Get unread count for badge.

##### POST /admin/notifications/{id}/read

Mark a notification as read.

##### POST /admin/notifications/read-all

Mark all notifications as read.

##### DELETE /admin/notifications/{id}

Delete a notification.

##### DELETE /admin/notifications/clear-read

Delete all read notifications.

##### POST /admin/notifications/test

Send a test notification to the current admin.

##### POST /admin/notifications/broadcast

Send a notification to all admins.

**Request:**
```json
{
  "title": "Server Maintenance",
  "message": "Server will be down at 3 AM",
  "type": "warning",
  "priority": "urgent",
  "link": "/admin/dashboard"
}
```

---

#### Admin IP Bans

##### GET /admin/ip-bans

List IP bans.

##### POST /admin/ip-bans

Create an IP ban.

##### GET /admin/ip-bans/{id}

Get a specific IP ban.

##### PATCH /admin/ip-bans/{id}

Update an IP ban.

##### DELETE /admin/ip-bans/{id}

Delete an IP ban.

---

#### Admin User Tools

##### GET /admin/user-tools/search

Search users by username, email, or ID.

**Query Parameters:**
- `q` (string) — Search query

##### GET /admin/user-tools/{id}

Get comprehensive user data for tools view.

##### GET /admin/user-tools/{id}/inventory

Get user's inventory items.

##### GET /admin/user-tools/{id}/timers

Get user's active timers and cooldowns.

##### DELETE /admin/user-tools/{id}/timers/{timerType}

Clear a specific timer for a user.

Timer types: `crime`, `theft`, `gym`, `travel`, `jail`, `hospital`

##### GET /admin/user-tools/{id}/activity

Get user's activity history.

##### GET /admin/user-tools/{id}/flags

Get user's flags and tags (banned, muted, verified, donator, roles, custom).

##### POST /admin/user-tools/{id}/flags

Add a custom flag to a user.

**Request:**
```json
{
  "type": "suspicious_activity",
  "label": "Suspicious Activity",
  "value": "high-rolling",
  "reason": "Multiple large transactions",
  "severity": "warning"
}
```

##### DELETE /admin/user-tools/{id}/flags/{flagType}

Remove a flag from a user.

##### GET /admin/user-tools/{id}/jobs

Get job/task statistics for a user (crimes, missions, organized crimes).

##### GET /admin/user-tools/{id}/job-history

Get detailed job history with timestamps.

**Query Parameters:**
- `type` (string) — Filter by type (crime_attempt, organized_crime, theft_attempt, gym_train)
- `limit` (int, default: 50)

---

#### Admin Activity Logs

##### GET /admin/activity

Get all activity logs.

**Query Parameters:**
- `limit` (int, default: 100)
- `type` (string) — Filter by activity type

##### GET /admin/activity/recent

Get recent activity (limit default: 50).

##### GET /admin/activity/user/{userId}

Get activity for a specific user.

##### GET /admin/activity/suspicious

Get flagged suspicious activity.

##### POST /admin/activity/clean

Delete old activity logs.

---

#### Admin Cache Management

##### POST /admin/cache/clear

Clear the application cache.

##### POST /admin/cache/clear-user/{userId}

Clear cache for a specific user.

##### POST /admin/cache/warm-up

Warm up the cache (pre-load common data).

---

#### Admin Staff Chat

##### GET /admin/staff-chat/messages

Get staff chat messages.

##### POST /admin/staff-chat/messages

Send a staff chat message.

##### GET /admin/staff-chat/unread

Get unread staff chat messages.

---

#### Admin Backups

##### GET /admin/backups

List all backups with stats.

##### GET /admin/backups/settings

Get backup configuration.

##### PUT /admin/backups/settings

Update backup schedule settings.

##### PUT /admin/backups/storage

Update backup storage configuration.

##### POST /admin/backups/test-storage

Test backup storage connectivity.

##### POST /admin/backups

Create a new backup.

##### GET /admin/backups/{id}/download

Download a backup file.

##### POST /admin/backups/{id}/restore

Restore from a backup.

##### DELETE /admin/backups/{id}

Delete a backup.

---

#### Admin System Health

##### GET /admin/system/health

Get comprehensive system health data.

**Response (200):**
```json
{
  "resources": {
    "cpu": 45,
    "cpu_cores": 8,
    "memory": 62,
    "memory_used": "8.5 GB",
    "memory_total": "16.0 GB",
    "disk": 55,
    "disk_used": "120.5 GB",
    "disk_total": "250.0 GB",
    "network_up": "125 KB/s",
    "network_down": "890 KB/s"
  },
  "services": [
    { "name": "Web Server", "status": "running", "description": "nginx/1.24" },
    { "name": "PHP-FPM", "status": "running", "description": "PHP 8.2" },
    { "name": "MySQL", "status": "running", "description": "MySQL 8.0" }
  ],
  "queue": { "pending": 5, "processed": 15000, "failed": 0 },
  "cache": { "driver": "Redis", "hit_rate": 89.5, "memory_used": "45 MB" },
  "database": { "driver": "MySQL", "connected": true, "size": "2.5 GB" },
  "errors": [ { "time": "2 hours ago", "level": "error", "message": "..." } ],
  "scheduled_tasks": [
    { "name": "Energy Regeneration", "command": "energy:refill", "schedule": "* * * * *", "status": "success" }
  ]
}
```

##### POST /admin/system/queue/retry-failed

Retry all failed queue jobs.

##### POST /admin/system/cache/clear

Clear a specific cache type.

**Request:**
```json
{ "type": "all" }
```

Cache types: `all`, `views`, `config`, `routes`

---

#### Admin Developer Tools

##### GET /admin/developer/hooks

List all registered hooks in the system.

##### GET /admin/developer/metrics

Get system metrics introspection data.

---

#### Admin API Keys

##### GET /admin/api-keys

List all API keys.

##### GET /admin/api-keys/permissions

Get available API key permission scopes.

##### GET /admin/api-keys/analytics

Get API key usage analytics.

##### POST /admin/api-keys

Create a new API key.

##### GET /admin/api-keys/{id}

Get a specific API key.

##### PATCH /admin/api-keys/{id}

Update an API key.

##### DELETE /admin/api-keys/{id}

Delete an API key.

##### POST /admin/api-keys/{id}/toggle

Toggle an API key active/inactive.

##### POST /admin/api-keys/{id}/regenerate-secret

Regenerate an API key's secret.

##### GET /admin/api-keys/{id}/logs

Get usage logs for a specific API key.

---

#### Admin Sidebar

##### GET /admin/sidebar

Get the admin sidebar menu for the authenticated user.

**Response (200):**
```json
{
  "menu": [
    { "label": "Dashboard", "icon": "HomeIcon", "route": "admin.dashboard" },
    { "label": "Users", "icon": "UsersIcon", "route": "admin.users" }
  ]
}
```

---

### Installer Endpoints

Installer endpoints are under `/install/` (web routes, not part of the API). They are available before the application is installed and are protected by the `installer` middleware.

| Method | Path | Description |
|--------|------|-------------|
| GET | `/install/` | Serve installer UI (HTML) |
| GET | `/install/check` | Check installation status |
| GET | `/install/requirements` | Check PHP version, extensions, permissions |
| GET | `/install/database/check` | Check database configuration readiness |
| POST | `/install/database` | Test and save database configuration |
| GET | `/install/settings/check` | Check app settings readiness |
| POST | `/install/settings` | Save app settings (name, URL, environment) |
| GET | `/install/install/check` | Check migration/seed readiness |
| POST | `/install/install/process` | Run full installation (migrate, seed, storage link) |
| POST | `/install/install/clear-cache` | Step: Clear configuration cache |
| POST | `/install/install/migrate` | Step: Run database migrations |
| POST | `/install/install/seed` | Step: Seed database |
| POST | `/install/install/storage-link` | Step: Create storage symlink |
| POST | `/install/install/finalize` | Step: Optimize and remove installer |
| GET | `/install/setup-admin/check` | Check admin setup readiness |
| POST | `/install/setup-admin` | Create admin user |
| POST | `/install/license` | Validate and store license key |
| GET | `/install/license/check` | Get license status |
| POST | `/install/complete` | Mark installation as complete |

---

### License Callback (Unversioned)

#### POST /api/license/callback

Server-to-server callback for license activation notifications. This endpoint is **not** under `/api/v1/` — it must never change so that external licensing servers can reach it.

Rate limited: 10 requests per minute.

**Headers:**
- `X-Signature`: HMAC-SHA256 signature of the raw request body using the `LICENSE_CALLBACK_SECRET`.

**Request:**
```json
{
  "license_id": "ABC123",
  "domain": "example.com",
  "ip": "1.2.3.4"
}
```

**Response (200):**
```json
{ "success": true }
```
