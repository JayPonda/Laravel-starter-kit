# 🚀 Laravel Quick-Start Boilerplate (Sail Edition)

[![Laravel](https://img.shields.io/badge/Laravel-11.x-FF2D20?style=for-the-badge&logo=laravel)](https://laravel.com)
[![PHP](https://img.shields.io/badge/PHP-8.3-777BB4?style=for-the-badge&logo=php)](https://php.net)
[![Docker](https://img.shields.io/badge/Docker-Enabled-2496ED?style=for-the-badge&logo=docker)](https://www.docker.com)
[![License](https://img.shields.io/badge/License-MIT-green?style=for-the-badge)](LICENSE)

A high-performance, enterprise-ready Laravel template designed to bridge the gap between development and production. Pre-configured with **Laravel Sail**, **Redis**, **MinIO**, and a robust **Service-Layer Architecture**.

> **Frontend modes:** This starter kit ships two interchangeable frontends — **Blade** (server-rendered Laravel views + session auth) and **Public** (standalone static HTML pages talking to the REST API). At setup you pick exactly one via `--frontend`, so the project never carries both. See [Frontend Mode](#frontend-mode) below.

---

## 🌟 Key Highlights

- **⚡ Instant Setup**: Go from `git clone` to a working dashboard in under 3 minutes with automated scripts.
- **🐳 Container First**: Production-parity development environment using Laravel Sail (MySQL 8, Redis, MinIO).
- **🏗 Service Layer Architecture**: Clean separation of concerns with dedicated Service classes for business logic.
- **🔐 Integrated Auth**: Ready-to-use API (Sanctum) and Web (Blade) authentication workflows.
- **🧪 Test Driven**: 95%+ test coverage with pre-configured PHPUnit and Laravel Pint for code quality.

---

## 🏁 Getting Started

Choose the setup path that best matches your local environment.

### 🛠️ Prerequisites
- **Docker Desktop** (Required for Case A & B)
- **PHP 8.3+** & **Composer** (Required for Case B & C)

---

### 📦 Installation Options

<details open>
<summary><b>1. Containerized Development (Laravel Sail) — <i>Recommended</i></b></summary>
<p>Best for a consistent, isolated environment. No local database or Redis installation required.</p>

```bash
# 1. Clone & Configure
git clone https://github.com/JayPonda/Laravel-starter-kit laraKit && cd laraKit
cp .env.example .env

# 2. Stop all the containers
docker stop $(docker ps -q)

# 3. Automated Run
make run i=1

# Choose the frontend rendering mode (default: blade)
make run i=1 frontend=public

# 4. Access
# Blade mode   -> http://localhost:12354  (Laravel server-rendered views)
# Public mode  -> http://localhost:18081  (standalone static HTML + API)
```

</details>

<details>
<summary><b>2. Hybrid Development (Local PHP + Docker Infrastructure)</b></summary>
<p>Best for maximizing performance by running PHP locally while keeping heavy infrastructure (MySQL, Redis, MinIO) in containers.</p>

1. **Configure `.env`**:
   ```env
   DB_HOST=127.0.0.1 | DB_PORT=3311
   REDIS_HOST=127.0.0.1 | REDIS_PORT=6379
   ```
2. **Start Infrastructure**:
   ```bash
   docker compose up -d mysql redis minio
   ```
3. **Initialize App**:
   ```bash
   composer install
   php artisan key:generate
   php artisan migrate --seed
   php artisan storage:link
   ```
4. **Run**: 
   - Backend: `php artisan serve`
   - Standalone Frontend (Verification): `http://localhost:8081`
</details>

<details>
<summary><b>3. Native Development (Full Local Host)</b></summary>
<p>Best for minimal overhead if you already have a full LEMP/WAMP stack installed locally.</p>

```bash
# Ensure local MySQL and Redis services are running
composer install
php artisan key:generate
php artisan migrate --seed
php artisan serve

# Access: http://localhost:8000
```
</details>

---

## 🎨 Frontend Mode

The kit never ships both frontends at once. During setup you choose one with the
`--frontend` flag (or `frontend=` for `make run`). The default is **blade**.

```bash
php run.php -i --frontend=blade    # or: public
make run i=1 frontend=public
```

| Mode | What you get | Access |
| :--- | :--- | :--- |
| `blade` (default) | Laravel server-rendered views, session auth, `routes/web.php` | Backend at `:12354` |
| `public` | Standalone static HTML in `public/`, REST API auth (Sanctum) | Frontend at `:18081` |

Internally, `setup/apply-frontend.php` copies the chosen preset from
`presets/<mode>/` into the project and removes the other variant's files. Run it
with `--dry-run` to preview changes without writing anything:

```bash
php setup/apply-frontend.php --mode=public --dry-run
```

## 🛠 Development Workflow

Common commands available via `Makefile` for streamlined development:

| Command | Action |
| :--- | :--- |
| `make up` | Start all Docker containers |
| `make down` | Stop all Docker containers |
| `make test` | Run the full test suite |
| `make migrate` | Run database migrations |
| `make shell` | Access the backend container shell |
| `make logs` | Stream application logs |
| `make url` | Show all service URLs & Endpoints |

---

## 🏗 Project Architecture

This boilerplate follows the **Service Layer Pattern** to ensure scalability and maintainability.

```text
app/
├── Http/Controllers/    # Lean controllers handling only HTTP logic
├── Services/            # Business logic encapsulated in reusable services
├── Models/              # Eloquent models and relationships
└── Console/Commands/    # Custom CLI tools (e.g., user:create)
```

---

## 🐳 Infrastructure Stack

| Service | Port (Internal) | Port (External) | Description |
| :--- | :--- | :--- | :--- |
| **Backend** | 80 | `12354` | Laravel 11.x (API/Blade) |
| **Frontend** | 80 | `18081` | Standalone HTML (API Verification) |
| **MySQL** | 3306 | `33101` | Database Storage |
| **Redis** | 6379 | `63079` | Cache & Queue |
| **MinIO** | 9000 | `19000` | S3-Compatible Object Storage |
| **MinIO Console** | 8900 | `18900` | MinIO Web Console |

---

## 🩺 Verification & Health Check

Verify your setup using the standalone frontend to ensure API connectivity:

1. **Standalone Check** (public mode): Visit `http://localhost:18081/index.html`. This page uses pure JavaScript to verify the Backend, Database, and Redis connections. In blade mode, use `http://localhost:12354/` instead.
2. **API Endpoint**: `curl http://localhost:12354/api/` (should return `{"status":"up"}`).
3. **Functional Test** (public mode): Use `http://localhost:18081/login.html` to test the full authentication flow via API.

---

## 🧪 Quality Assurance

We maintain high standards through automated checks:
```bash
# Run tests
php artisan test

# Fix code style
composer lint:fix
```

---

## 🚨 Troubleshooting

### `getaddrinfo for mysql failed: Name or service not known`

If migrations fail with a DNS resolution error for the `mysql` hostname, the cause is almost always a **port conflict** from another Docker project. When a port is already in use, Docker silently fails to attach the container to the network, breaking inter-service DNS.

**Symptoms:**
```
SQLSTATE[HY000] [2002] php_network_getaddresses: getaddrinfo for mysql failed
```

**Fix:**
```bash
# 1. Identify conflicting containers (common culprits: ports 3311, 6379, 8081, 9000)
docker ps --format 'table {{.Names}}\t{{.Ports}}'

# 2. Stop the conflicting containers
docker stop <conflicting-container-name>

# 3. Recreate the network and restart
docker compose down && docker compose up -d

# 4. Verify all containers share the same network
docker ps --format 'table {{.Names}}\t{{.Networks}}'

# 5. Re-run migrations
docker compose exec -T backend php artisan migrate --force
```

---

## 📄 License
This project is open-sourced software licensed under the [MIT license](LICENSE).
