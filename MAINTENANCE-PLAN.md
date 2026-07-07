# Maintenance Plan — pepvguides.com

## Priority: Critical
- [ ] **Fix cron job path** — points to stale `/var/www/vhosts/pepvguides.com/backend/`, should be `repo/backend`
- [ ] **Fix queue worker path** — supervisord points to stale path, needs updating + restart

## Priority: Medium
- [ ] **Supervise Reverb** — add to supervisord with auto-restart (currently runs as root, no restart)
- [ ] **Remove test accounts** — IDs 1 (admin@example.com), 40 (test@pepvguides.com), 41 (free@test.com)
- [ ] **Remove SSE dead code** — delete `SseController.php` and its route in `web.php`

## Priority: Low
- [ ] **Clean stale assets** — remove old hashed JS files from `public/assets/` (6 old versions)
- [ ] **Clean stale sessions** — clear 32 stale rows from MySQL `sessions` table
- [ ] **Configure email** — switch from `MAIL_MAILER=log` to real SMTP
- [ ] **Hide install directory** — remove or protect `public/install/`
- [ ] **Clean test guest sessions** — remove test curl guest IDs from DB
- [ ] **Commit + deploy all changes** — git commit uncommitted work
