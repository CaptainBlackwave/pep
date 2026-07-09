---
name: laravelcp_setup
description: Cheatsheet for setting up, seeding, and licensing the LaravelCP application from a fresh copy.
---

# LaravelCP Setup & Initialization Guide

When asked to set up the application from a fresh copy, or if encountering "invalid credentials" or "panel locked / license required" errors on a fresh install, follow these steps exactly:

## 0. Docker Environment (CRITICAL)
- **This project runs in a containerized Docker environment.**
- **NEVER** run `php`, `composer`, or `artisan` commands directly on the host machine.
- All backend commands MUST be executed inside the `laravelcp_backend` container.
- Example: `docker exec laravelcp_backend php artisan <command>`
- The backend files are located in the `backend/` directory on the host.

## 1. Initial Setup & Container Startup
If starting from a completely fresh state:
1. Ensure the old containers/volumes are removed: `docker compose down -v`
2. Create the environment file: `cp backend/.env.example backend/.env` (or use PowerShell equivalent).
3. **CRITICAL:** Add `SEED_DEVELOPMENT_USERS=true` to `backend/.env`. If this is missing, development users (`admin` and `testuser`) will NOT be created during seeding, resulting in login failures!
4. Start the containers and build them: `docker compose up -d --build`
5. Wait for the containers to become healthy. The backend container is named `laravelcp_backend`.

## 2. Application Key Generation
- Ensure `APP_KEY` is valid. If it says `base64:placeholder` (or is missing), you MUST run:
  `docker exec laravelcp_backend php artisan key:generate`
  This prevents the `Encrypter` from crashing and throwing 500 errors on the frontend.

## 3. Database Initialization & Seeding
- Run the migrations and seeders:
  `docker exec laravelcp_backend php artisan migrate:fresh --seed`
- Verify the seeded users exist by logging in or checking the database. The default admin credentials are `admin` / `admin123`.

## 4. License Generation & Activation
The application uses a strict license gate. To bypass the "Panel Locked" screen on a local deployment:
1. Generate the RSA keypair (if not already done): 
   `docker exec laravelcp_backend php artisan license:setup`
2. Generate an unlimited lifetime license: 
   `docker exec laravelcp_backend php artisan license:generate --domain="*" --tier="unlimited" --max-users=0 --plugins="all" --customer="Admin" --email="admin@example.com" --expires="lifetime" --no-interaction`
3. Activate the license: Take the `LCP-UNL-...` key output from the previous step and write it directly to `backend/storage/license_key`.
