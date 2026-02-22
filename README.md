# LaravelCP - Persistent Browser-Based Game (PBBG)

A comprehensive Persistent Browser-Based Game platform built with modern technologies, featuring a Vue 3 frontend and Laravel 11 backend with a modular plugin architecture.

[![Frontend](https://img.shields.io/badge/Frontend-Vue%203-42b883.svg)](https://vuejs.org/)
[![Backend](https://img.shields.io/badge/Backend-Laravel%2011-red.svg)](https://laravel.com)
[![PHP](https://img.shields.io/badge/PHP-8.3+-blue.svg)](https://php.net)
[![License](https://img.shields.io/badge/License-MIT-green.svg)](LICENSE)

---

## Project Structure

```
LaravelCP/
├── frontend/                    # Vue 3 + TypeScript + Vite
│   ├── src/                     # Source code
│   │   ├── components/          # Vue components
│   │   ├── views/               # Page views
│   │   ├── stores/              # Pinia state management
│   │   ├── services/            # API & WebSocket services
│   │   └── router/              # Vue Router configuration
│   ├── e2e/                     # Playwright E2E tests
│   ├── package.json
│   └── vite.config.ts
│
├── backend/                     # Laravel 11 + PHP 8.3
│   ├── app/
│   │   ├── Core/                # Core system components
│   │   └── Plugins/             # Game feature plugins (28 built-in)
│   ├── routes/                  # API routes
│   ├── database/                # Migrations & seeders
│   ├── composer.json
│   └── Dockerfile
│
├── docker-compose.yml           # Full-stack Docker configuration
├── LaravelCP.code-workspace     # VS Code workspace configuration
└── README.md
```

---

## Quick Start

### Prerequisites

- **Docker** (recommended) or:
  - Node.js 18+
  - PHP 8.3+
  - Composer 2.x
  - MySQL 8.0+

### Using Docker (Recommended)

1. **Clone the repository:**
   ```bash
   git clone https://github.com/Lindon11/LaravelCP.git
   cd LaravelCP
   ```

2. **Start all services:**
   ```bash
   docker compose up -d
   ```

3. **Install dependencies:**
   ```bash
   # Frontend dependencies
   docker compose exec frontend npm install
   
   # Backend dependencies
   docker compose exec backend composer install
   ```

4. **Setup the backend:**
   ```bash
   # Copy environment file
   cp backend/.env.example backend/.env
   
   # Generate app key
   docker compose exec backend php artisan key:generate
   
   # Run migrations
   docker compose exec backend php artisan migrate
   
   # Seed the database (optional)
   docker compose exec backend php artisan db:seed
   ```

5. **Access the application:**
   - **Frontend:** http://localhost:5175
   - **Backend API:** http://localhost:8001
   - **phpMyAdmin:** http://localhost:8082

---

## Development Setup

### Frontend Development

```bash
cd frontend

# Install dependencies
npm install

# Start development server
npm run dev

# Type checking
npm run type-check

# Lint
npm run lint

# Build for production
npm run build
```

#### Frontend Tech Stack
- **Vue 3** - Progressive JavaScript framework
- **TypeScript** - Type-safe JavaScript
- **Vite** - Fast build tool
- **Pinia** - State management
- **Vue Router** - Client-side routing
- **Axios** - HTTP client

### Backend Development

```bash
cd backend

# Install dependencies
composer install

# Setup environment
cp .env.example .env
php artisan key:generate

# Run migrations
php artisan migrate

# Start development server
php artisan serve --port=8001

# Generate API documentation
php artisan scribe:generate
```

#### Backend Tech Stack
- **Laravel 11** - PHP framework
- **Laravel Sanctum** - API authentication
- **Laravel Reverb** - WebSocket server
- **MySQL 8.0** - Database
- **Scribe** - API documentation

---

## Testing

### Frontend Tests

```bash
cd frontend

# Unit tests (Vitest)
npm run test:unit

# E2E tests (Playwright)
npx playwright install        # First time only
npm run test:e2e

# Run specific E2E test
npm run test:e2e -- --project=chromium
```

### Backend Tests

```bash
cd backend

# Run all tests
php artisan test

# Run specific test
php artisan test --filter=ExampleTest

# Run with coverage
php artisan test --coverage
```

---

## Game Features

### Core System
- 🔐 **Authentication** - Sanctum-based API auth with role-based access control
- 👥 **User Management** - Player profiles, stats, inventory, progression
- 🎮 **Game Engine** - Timers, cooldowns, experience, leveling, rank system
- 🔔 **Notifications** - Real-time player and admin notifications

### Built-in Plugins (28)

| Plugin | Description |
|--------|-------------|
| Achievements | Track and reward player accomplishments |
| Bank | Player banking with interest and transfers |
| Casino | Slots, dice, roulette gambling games |
| Chat | Real-time chat with channels and DMs |
| Combat | PvP and PvE combat system |
| Crimes | Crime activities with cooldowns and rewards |
| DailyRewards | Daily login bonuses |
| Detective | Player investigation system |
| Drugs | Drug trade mechanics |
| Education | Skill training courses |
| Employment | Job system with shifts |
| Forum | Community discussion boards |
| Gang | Gang/faction system |
| Hospital | Health recovery system |
| Inventory | Items, equipment, effects |
| Jail | Jail system with bailout/bustout |
| Leaderboards | Player rankings |
| Missions | Quest/mission system |
| OrganizedCrime | Group heist mechanics |
| Properties | Real estate ownership |
| Racing | Vehicle racing competitions |
| Stocks | Stock market trading |
| Theft | Theft from other players |
| Tickets | Support ticket system |
| Travel | Location-based travel |
| And more... | |

---

## API Documentation

The backend provides a comprehensive REST API. After starting the backend, access the auto-generated API documentation at:

```
http://localhost:8001/docs
```

### Key API Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/api/v1/login` | User authentication |
| POST | `/api/v1/register` | User registration |
| GET | `/api/v1/user` | Get current user |
| GET | `/api/v1/dashboard` | Player dashboard data |
| GET | `/api/v1/plugins/*` | Plugin-specific endpoints |
| GET | `/api/v1/admin/*` | Admin panel endpoints |

---

## Docker Services

The `docker-compose.yml` includes:

| Service | Port | Description |
|---------|------|-------------|
| `frontend` | 5175 | Vue 3 development server |
| `backend` | 8001 | Laravel Apache/PHP |
| `mysql` | 3307 | MySQL 8.0 database |
| `phpmyadmin` | 8082 | Database management UI |

### Docker Commands

```bash
# Start all services
docker compose up -d

# View logs
docker compose logs -f

# Stop all services
docker compose down

# Rebuild containers
docker compose build --no-cache

# Access backend container
docker compose exec backend bash

# Access frontend container
docker compose exec frontend sh
```

---

## Deployment

See [DEPLOYMENT.md](./DEPLOYMENT.md) for detailed production deployment instructions.

### Quick Production Build

```bash
# Frontend
cd frontend
npm run build
# Deploy the dist/ folder

# Backend
cd backend
composer install --optimize-autoloader --no-dev
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

---

## Contributing

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit changes (`git commit -m 'Add amazing feature'`)
4. Push to branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

---

## Documentation

- [Deployment Guide](./DEPLOYMENT.md) - Production deployment instructions
- [Integration Guide](./INTEGRATION_GUIDE.md) - Frontend-backend integration details
- [Backend README](./backend/README.md) - Backend-specific documentation
- [Plugin Development](./backend/docs/PLUGIN_HOOKS.md) - Creating custom plugins

---

## Support

- 🐛 [Issue Tracker](https://github.com/Lindon11/LaravelCP/issues)
- 💬 [Discussions](https://github.com/Lindon11/LaravelCP/discussions)

---

## License

This project is open-sourced software licensed under the [MIT license](./backend/LICENSE).