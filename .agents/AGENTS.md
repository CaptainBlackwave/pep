# Local Environment Rules
- We use a dockerized environment for this project.
- The backend is in the 'backend' folder.
- All artisan or php commands MUST be run using `docker exec -it laravelcp_backend php artisan ...` (or `docker exec -it laravelcp_backend php ...`). Do NOT run `php` or `artisan` directly on the host.
