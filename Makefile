.PHONY: help up down restart migrate fresh seed test shell run config queue create-user db-reset db-bash

SAIL := ./vendor/bin/sail

help: ## Show this help
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-15s\033[0m %s\n", $$1, $$2}'

run: ## Fully automate setup: Docker + Migrations + Tests. Use 'make run i=1' to include installations.
	php run.php $(if $(i),-i)

up: ## Start Sail containers
	$(SAIL) up -d

down: ## Stop Sail containers
	$(SAIL) down

restart: down up ## Restart Sail containers

migrate: ## Run database migrations inside Sail
	docker compose exec backend php artisan migrate

fresh: ## Freshly migrate and seed the database
	docker compose exec backend php artisan migrate:fresh --seed

seed: ## Run database seeders inside Sail
	docker compose exec backend php artisan db:seed

create-user: ## Create a new user (usage: make create-user name="John Doe" email="john@example.com")
	docker compose exec backend php artisan user:create "$(name)" "$(email)"

healthcheck: ## Check health of all running services
	@echo -e "\033[36m>>> Backend API:\033[0m"
	@curl -sf http://localhost:$(shell sed -n 's/^APP_PORT=//p' .env | head -n 1 | grep . || echo 12354)/api/ | python3 -m json.tool 2>/dev/null || echo -e "\033[31mBackend is DOWN\033[0m"
	@echo -e "\n\033[36m>>> Frontend:\033[0m"
	@curl -sf -o /dev/null -w "HTTP %{http_code}\n" http://localhost:$(shell sed -n 's/^FORWARD_FRONTEND_PORT=//p' .env | head -n 1 | grep . || echo 18081)/ || echo -e "\033[31mFrontend is DOWN\033[0m"
	@echo -e "\n\033[36m>>> Docker Containers:\033[0m"
	@docker compose ps --format "table {{.Name}}\t{{.Status}}" 2>/dev/null || echo -e "\033[31mNo containers running\033[0m"

queue: ## Start the Sail queue worker
	docker compose exec backend php artisan queue:work

dev: ## Start Vite development server and Queue worker concurrently
	docker compose exec backend npx concurrently -c "#93c5fd,#c4b5fd,#fb7185" "php artisan queue:listen" "npm run dev" --names=queue,vite

logs: ## Stream application logs using Laravel Pail
	docker compose exec backend php artisan pail

logs-app: ## Stream raw Docker logs for the backend container
	docker compose logs -f backend

logs-db: ## Stream raw Docker logs for the database container
	docker compose logs -f mysql

logs-fe: ## Stream raw Docker logs for the frontend container
	docker compose logs -f frontend

clear: ## Clear logs and cached configuration
	docker compose exec backend php artisan config:clear
	docker compose exec backend php artisan cache:clear || true
	docker compose exec backend php artisan view:clear

test: ## Run tests inside Sail
	docker compose exec backend php artisan test

shell: ## Open a bash shell in the Sail app container
	docker compose exec backend /bin/bash

tinker: ## Start a Laravel Tinker session
	docker compose exec backend php artisan tinker

bash: ## Run a command inside the container (usage: make bash cmd="ls -la")
	docker compose exec backend /bin/bash -c "$(cmd)"

url: ## Show all service URLs
	@echo -e "\033[32mBackend: \033[0m http://localhost:$(shell sed -n 's/^APP_PORT=//p' .env | head -n 1 | grep . || echo 12354)"
	@echo -e "\033[32mFrontend:\033[0m http://localhost:$(shell sed -n 's/^FORWARD_FRONTEND_PORT=//p' .env | head -n 1 | grep . || echo 8081)"
	@echo -e "\033[32mDatabase:\033[0m mysql://$(shell sed -n 's/^DB_USERNAME=//p' .env | head -n 1):$(shell sed -n 's/^DB_PASSWORD=//p' .env | head -n 1)@127.0.0.1:$(shell sed -n 's/^FORWARD_DB_PORT=//p' .env | head -n 1 | grep . || echo 3311)/$(shell sed -n 's/^DB_DATABASE=//p' .env | head -n 1)"
	@echo -e "\033[32mMinio:   \033[0m http://localhost:$(shell sed -n 's/^FORWARD_MINIO_PORT=//p' .env | head -n 1 | grep . || echo 9000)"
	@echo -e "\033[32mRedis:   \033[0m redis://127.0.0.1:$(shell sed -n 's/^FORWARD_REDIS_PORT=//p' .env | head -n 1 | grep . || echo 6379)"

url-backend: ## Show Backend URL
	@echo "http://localhost:$(shell sed -n 's/^APP_PORT=//p' .env | head -n 1 | grep . || echo 12354)"

url-frontend: ## Show Frontend URL
	@echo "http://localhost:$(shell sed -n 's/^FORWARD_FRONTEND_PORT=//p' .env | head -n 1 | grep . || echo 8081)"

url-db: ## Show Database Connection String
	@echo "mysql://$(shell sed -n 's/^DB_USERNAME=//p' .env | head -n 1):$(shell sed -n 's/^DB_PASSWORD=//p' .env | head -n 1)@127.0.0.1:$(shell sed -n 's/^FORWARD_DB_PORT=//p' .env | head -n 1 | grep . || echo 3311)/$(shell sed -n 's/^DB_DATABASE=//p' .env | head -n 1)"

crud: ## Create a full CRUD stack (usage: make crud name=Post)
	docker compose exec backend php artisan make:crud $(name)
	sudo chown -R $(USER):$(USER) app/Models app/Http/Controllers/database/migrations database/factories database/seeders resources/views routes

model: ## Generate model + migration + factory + seeder (usage: make model name=Post)
	docker compose exec backend php artisan make:model $(name) -mfs
	sudo chown -R $(USER):$(USER) app/Models app/Http/Controllers/database/migrations database/factories database/seeders resources/views routes

db-reset: ## Stop containers and remove the MySQL volume (destroys all database data)
	@read -p "This will DELETE all database data. Continue? [y/N] " confirm && [ "$$confirm" = "y" ] || { echo "Aborted"; exit 1; }
	docker compose stop mysql
	docker volume rm "$$(docker volume ls -q | grep sail-mysql | head -n 1)" 2>/dev/null || true
	@echo "MySQL volume removed. Run 'make up' to recreate it."

db-bash: ## Open a MySQL client shell as root using .env credentials
	docker compose exec mysql mysql -uroot -p$(shell sed -n 's/^DB_PASSWORD=//p' .env | head -n 1) --prompt="\\u@%> " $(shell sed -n 's/^DB_DATABASE=//p' .env | head -n 1)

ini-pull: ## (Recovery) Copy php.ini from container to local docker/8.3/php.ini
	docker compose cp backend:/etc/php/8.3/cli/conf.d/99-sail.ini ./docker/8.3/php.ini

config: ## Generate MySQL configuration (used by Docker volumes)
	php setup/generate-db-sql.php
