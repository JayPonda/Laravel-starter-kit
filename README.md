# 🚀 Laravel Quick-Start Boilerplate

A battle-tested, high-performance Laravel template designed to take you from "Zero to Dashboard" in under 3 minutes. This boilerplate comes pre-configured with essential infrastructure, dual authentication layers, and a clean Service-Oriented Architecture.

---

## ✨ Key Features

-   **⚡ One-Command Setup**: Fully automated environment initialization via `make run` or `php run.php`.
-   **🐳 Docker-Ready Infrastructure**: Pre-configured MySQL 8 and Redis 7 with automatic configuration generation.
-   **🔐 Dual Authentication Layers**:
    *   **API (Sanctum)**: Pre-built endpoints for Register, Login, Me, Logout, and Password Reset.
    *   **Web (Blade)**: Ready-to-use views for Login, Registration, and a protected Dashboard.
- **🏗 Clean Architecture**: Implements the **Service Layer Pattern** (`UserService`) to keep controllers lean and business logic reusable.
- **🛠 Standalone Setup Scripts**:
    *   `setup/generate-db-sql.php`: Auto-generates MySQL initialization scripts without framework overhead.
    *   `setup/generate-redis-conf.php`: Auto-generates Redis configuration independently.
- **🛠 Custom Artisan Tools**: 
    *   `user:create` & `user:change-password`: Manage users directly from the CLI.
-   **🧪 Quality & Testing**: 
    *   **95%+ Coverage**: Comprehensive Unit and Feature tests pre-written.
    *   **Laravel Pint**: Pre-configured linting for clean, consistent code.
    *   **HTML Coverage Reports**: Built-in support for Xdebug coverage visualization.
-   **🌐 Frontend Integration**: Vite-ready with Blade templates and a **Standalone API Client** (`auth.html`) for immediate API testing.

---

## 🚀 Quick Start (3 Minutes)

The fastest way to get the project running:

```bash
# 1. Clone the template
git clone <repo-url> my-project && cd my-project/template

# 2. Run the automated setup (Installs dependencies, starts Docker, migrates, and tests)
make run i=1
```

*Note: Requires Docker and PHP 8.3+ installed locally.*

---

## 🛠 Manual Installation & Setup

If you prefer step-by-step control:

### 1. Environment Setup
```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
```

### 2. Infrastructure
Generate configs (standalone scripts) and start Docker containers:
```bash
php setup/generate-db-sql.php
php setup/generate-redis-conf.php
# OR simply: make config

make up
```

### 3. Database Initialization
```bash
php artisan migrate
php artisan storage:link
```

### 4. Verification
```bash
php artisan test
php artisan serve
```

---

## 📖 Project Architecture

### Service Layer Pattern
This project avoids "Fat Controllers" by encapsulating business logic in Services.
- **Controllers**: Handle HTTP requests and responses.
- **Services (`app/Services`)**: Contain all business logic (e.g., `UserService.php`).
- **Models**: Simple data structures and relationships.

Example flow: `Request` → `Controller` → `Service` → `Response`.

### Standalone Setup Scripts
The `setup/` directory contains framework-independent PHP scripts that can run before `composer install`.
- `php setup/generate-db-sql.php`: Syncs your `.env` database settings with a `storage/database.sql` file used by Docker for initial setup.
- `php setup/generate-redis-conf.php`: Generates a production-ready Redis config based on your environment variables.

### Custom Artisan Commands
- `php artisan user:create`: Quickly bootstrap an admin user.
- `php artisan user:change-password`: Update user credentials via CLI.

---

## 🖥 Development Workflow

| Command | Description |
| :--- | :--- |
| `make run` | Full automation: Docker + Migrations + Test + Serve |
| `composer dev` | Concurrent mode: Server + Queue + Logs + Vite |
| `composer lint:fix` | Fix code style issues using Laravel Pint |
| `composer test:coverage` | Run tests and generate HTML report in `storage/coverage` |
| `make shell` | Jump into the application shell |

---

## 🛣 API & Web Endpoints

### API (Sanctum) - `prefix: /api`
- `POST /register`: Create new account.
- `POST /login`: Get Bearer token.
- `POST /reset-password`: Reset via old password.
- `GET /me`: Get current user (Requires Auth).
- `POST /logout`: Revoke token (Requires Auth).

### Web - `prefix: /`
- `/login` & `/register`: Beautiful Blade-based forms.
- `/dashboard`: Protected area for logged-in users.
- `/auth.html`: **Pro-Tip:** Open this file in your browser to test the API directly with a built-in JS client.

---

## 🧪 Testing & Quality

We take quality seriously. The project includes:
- **Feature Tests**: `tests/Feature/AuthControllerTest.php` ensures your auth flows never break.
- **Unit Tests**: `tests/Unit/UserServiceTest.php` validates core business logic in isolation.

Run all tests:
```bash
php artisan test
```

---

## 🐳 Docker Services

- **MySQL (Port 3311)**: Persistent data storage with auto-init SQL.
- **Redis (Port 6379)**: High-speed caching and queue management.

Configured in `docker-compose.yml`, these services are isolated and optimized for local development.

---

## 📄 License
The Laravel framework is open-source software licensed under the [MIT license](https://opensource.org/licenses/MIT).
