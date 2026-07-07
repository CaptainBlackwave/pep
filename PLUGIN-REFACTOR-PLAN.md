# Plugin Refactor Plan

Convert each core feature into an independent plugin with enable/disable toggle.

## Plugin Structure (per feature)
```
backend/app/Plugins/{slug}/
  plugin.json          # Manifest
  {Pascal}Plugin.php   # Main class (extends Plugin)
  routes/
    api.php            # Public + authenticated API routes
    admin.php          # Admin routes
  Controllers/
    {Feature}Controller.php
    Admin/
      {Feature}AdminController.php
  Models/
  database/migrations/
```

## Phase 1: Proof of Concept — Discussions Plugin
Extract discussions + categories + replies + voting + reporting into a plugin.

## Phase 2: Vendors Plugin
Profiles, reviews, products, documents, claims.

## Phase 3: Lab Results Plugin
Submit, publish, verify.

## Phase 4: Remaining
Announcements, Research Library, Members, Messaging, Search.

Each phase: build → test (`php artisan test`) → deploy → verify.
