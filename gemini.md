# Local Environment Rules
- We use a dockerized environment for this project.
- The backend is in the 'backend' folder.
- All artisan or php commands MUST be run using `docker exec laravelcp_backend php artisan ...` (or `docker exec laravelcp_backend php ...`). Do NOT run `php` or `artisan` directly on the host. Note: Omit the `-it` flag to prevent terminal hangs.

## Docker Containers
- `laravelcp_db` (mysql:8.0) mapped to `3307:3306`
- `laravelcp_pma` (phpmyadmin) mapped to `8082:80`
- `laravelcp_backend` (pep-backend) mapped to `8001:80`
- `laravelcp_frontend` (pep-frontend) mapped to `5175:5175`

## Frontend Development Rules
- Due to WSL2 volume mount limitations, the Vite dev server inside `laravelcp_frontend` does NOT detect file modifications made on the host.
- **CRITICAL**: Every time you modify files in the `frontend` directory, you MUST proactively run `docker restart laravelcp_frontend` to clear the Vite cache and load the new files before asking the user to review your changes.
