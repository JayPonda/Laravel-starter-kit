# 🚀 Laravel Quick-Start Boilerplate (Sail Edition)

A battle-tested, high-performance Laravel template designed to take you from "Zero to Dashboard" in under 3 minutes, now powered by **Laravel Sail**.

---

## ✨ Key Features

-   **⚡ One-Command Setup**: Fully automated environment initialization via `make run` or `php run.php`.
-   **🐳 Sail-First Infrastructure**: Pre-configured MySQL 8, Redis, and MinIO via Laravel Sail.
-   **🔐 Dual Authentication Layers**:
    *   **API (Sanctum)**: Pre-built endpoints for Register, Login, Me, Logout, and Password Reset.
    *   **Web (Blade)**: Ready-to-use views for Login, Registration, and a protected Dashboard.
- **🏗 Clean Architecture**: Implements the **Service Layer Pattern** (`UserService`) to keep controllers lean and business logic reusable.
- **🛠 Standalone Setup Scripts**:
    *   `setup/generate-db-sql.php`: Auto-generates MySQL initialization scripts for Docker volumes.
    *   `setup/generate-redis-conf.php`: Auto-generates Redis configuration independently.
- **🧪 Quality & Testing**: 
    *   **95%+ Coverage**: Comprehensive Unit and Feature tests pre-written.
    *   **Laravel Pint**: Pre-configured linting for clean, consistent code.
-   **🌐 Frontend Integration**: Vite-ready with Blade templates and a **Standalone API Client** (`auth.html`) for immediate API testing.

---

## 🚀 Quick Start (3 Minutes)

The fastest way to get the project running:

```bash
# 1. Clone the template
git clone <repo-url> my-project && cd my-project

# 2. Run the automated setup
make run
```

*Note: Requires Docker and PHP 8.3+ installed locally for the initial setup script.*

---

## 🛠 Manual Installation & Setup

If you prefer step-by-step control using Sail:

### 1. Environment Setup
```bash
cp .env.example .env
composer install
./vendor/bin/sail up -d
```

### 2. Database & Keys
```bash
./vendor/bin/sail artisan key:generate
./vendor/bin/sail artisan migrate
./vendor/bin/sail artisan storage:link
```

### 3. Verification
```bash
./vendor/bin/sail test
```

---

## 🖥 Development Workflow

| Command | Description |
| :--- | :--- |
| `make run` | Full automation: Sail Up + Migrations + Test |
| `make up` | Start Sail containers in the background |
| `make down` | Stop Sail containers |
| `make test` | Run all tests inside the Sail container |
| `make shell` | Jump into the application shell (Sail) |
| `composer lint:fix` | Fix code style issues using Laravel Pint |

---

## 📖 Project Architecture

### Service Layer Pattern
This project avoids "Fat Controllers" by encapsulating business logic in Services.
- **Controllers**: Handle HTTP requests and responses.
- **Services (`app/Services`)**: Contain all business logic (e.g., `UserService.php`).

Example flow: `Request` → `Controller` → `Service` → `Response`.

### Custom Artisan Commands
- `php artisan user:create`: Quickly bootstrap an admin user.
- `php artisan user:change-password`: Update user credentials via CLI.

---

## 🐳 Docker Services (Sail)

-   **MySQL (Internal Port 3306, External 3311)**: Persistent data storage.
-   **Redis (Port 6379)**: High-speed caching and queue management.
-   **MinIO (Port 9000)**: Local S3-compatible storage.

---

## 📄 License
The Laravel framework is open-source software licensed under the [MIT license](https://opensource.org/licenses/MIT).
