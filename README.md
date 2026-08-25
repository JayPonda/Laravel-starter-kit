# 🚀 Laravel Quick-Start Boilerplate (Sail Edition)

[![Laravel](https://img.shields.io/badge/Laravel-11.x-FF2D20?style=for-the-badge&logo=laravel)](https://laravel.com)
[![PHP](https://img.shields.io/badge/PHP-8.3-777BB4?style=for-the-badge&logo=php)](https://php.net)
[![Docker](https://img.shields.io/badge/Docker-Enabled-2496ED?style=for-the-badge&logo=docker)](https://www.docker.com)
[![License](https://img.shields.io/badge/License-MIT-green?style=for-the-badge)](LICENSE)

A high-performance, enterprise-ready Laravel template designed to bridge the gap between development and production. Pre-configured with **Laravel Sail**, **Redis**, **MinIO**, and a robust **Service-Layer Architecture**.

> **Branches & frontend:** This starter ships three long-lived branches. Each branch is self-contained, so there is **no setup flag and no file copying** — you simply check out the branch with the frontend you want.
> - `main` — backend + **Swagger** only (REST API, no UI).
> - `blade` — server-rendered **Laravel views** + session auth (`WebAuthController`, `routes/web.php`).
> - `html` — **standalone static HTML** in `public/` talking to the REST API (served by an extra `frontend` container).

---

## 🌟 Key Highlights

- **⚡ Instant Setup**: `git clone` → `git checkout <branch>` → `make run`. No flags to remember.
- **🐳 Container First**: Production-parity development environment using Laravel Sail (MySQL 8, Redis, MinIO).
- **🏗 Service Layer Architecture**: Clean separation of concerns with dedicated Service classes for business logic.
- **🔐 Integrated Auth**: Ready-to-use API (Sanctum) and Web (Blade) authentication workflows.
- **🧪 Test Driven**: Comprehensive PHPUnit suite with Laravel Pint for code quality.

---

## 🏁 Getting Started

### 📦 Installation (Laravel Sail) — *Recommended*

```bash
# 1. Clone & pick the frontend you want
git clone https://github.com/JayPonda/Laravel-starter-kit laraKit && cd laraKit
git checkout blade      # or: html  (use `main` for backend + Swagger only)

# 2. Configure & run
cp .env.example .env
docker stop $(docker ps -q)   # free conflicting ports
make run i=1

# 3. Access
#    blade  -> http://localhost:12354  (Laravel server-rendered views)
#    html   -> http://localhost:18081  (standalone static HTML, talks to the API)
#    main   -> http://localhost:12354/api/documentation  (Swagger UI)
```

<details>
<summary><b>Hybrid / Native development</b></summary>

**Hybrid (local PHP + Docker infra):** point `.env` at `127.0.0.1` for `DB_HOST`/`REDIS_HOST`, run `docker compose up -d mysql redis minio`, then `composer install && php artisan key:generate && php artisan migrate --seed && php artisan storage:link`. Backend: `php artisan serve`.

**Native (full local):** ensure MySQL/Redis run locally, then `composer install && php artisan key:generate && php artisan migrate --seed && php artisan serve` → http://localhost:8000.
</details>

---

## 🌿 Branches

| Branch | What you get | Access |
| :--- | :--- | :--- |
| `main` | Backend + Swagger UI only (no frontend) | API at `:12354`, docs at `/api/documentation` |
| `blade` | Laravel server-rendered views, session auth, `routes/web.php` | App at `:12354` |
| `html` | Standalone static HTML in `public/`, REST API auth (Sanctum) | Frontend at `:18081` |

**Workflow.** Shared backend work lands on `main`; to keep the variants current, merge `main` into `blade` and `html`
(`git switch blade && git merge main`). Frontend-only changes are made directly on the relevant branch.
Because `blade`/`html` only *add* frontend files on top of `main`, those syncs are normally conflict-free.

---

## 🛠 Development Workflow

Common commands available via `Makefile`:

| Command | Action |
| :--- | :--- |
| `make run i=1` | Full setup: containers + migrations + tests |
| `make up` / `make down` | Start / stop all Docker containers |
| `make test` | Run the full test suite |
| `make migrate` | Run database migrations |
| `make shell` | Open a backend container shell |
| `make swagger` | (Re)generate the OpenAPI docs (`main`) |

---

## 🏗 Project Architecture

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
| **Backend** | 80 | `12354` | Laravel 11.x (API + optional UI) |
| **Frontend** | 80 | `18081` | *(html branch only)* Standalone HTML served by nginx |
| **MySQL** | 3306 | `33101` | Database Storage |
| **Redis** | 6379 | `63079` | Cache & Queue |
| **MinIO** | 9000 | `19000` | S3-Compatible Object Storage |
| **MinIO Console** | 8900 | `18900` | MinIO Web Console |

---

## 🩺 Verification & Health Check

- **API**: `curl http://localhost:12354/api/` → `{"status":"up"}`.
- **blade**: visit `http://localhost:12354/` (renders the dashboard once logged in).
- **html**: visit `http://localhost:18081/index.html` — it verifies Backend, Database, and Redis via the API. Use `http://localhost:18081/login.html` for the full auth flow.
- **main**: open `http://localhost:12354/api/documentation` for the Swagger UI.

---

## 🧪 Quality Assurance

```bash
php artisan test     # full suite
composer lint:fix    # Laravel Pint
```

---

## 🚨 Troubleshooting

### `getaddrinfo for mysql failed: Name or service not known`

If migrations fail with a DNS resolution error for the `mysql` hostname, the cause is almost always a **port conflict** from another Docker project. When a port is already in use, Docker silently fails to attach the container to the network, breaking inter-service DNS.

```bash
docker ps --format 'table {{.Names}}\t{{.Ports}}'   # find conflicting containers
docker stop <conflicting-container-name>
docker compose down && docker compose up -d
docker compose exec -T backend php artisan migrate --force
```

---

## 📄 License

This project is open-sourced software licensed under the [MIT license](LICENSE).
