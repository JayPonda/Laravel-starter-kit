.PHONY: help up down restart migrate fresh seed test shell run config queue

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
	docker compose exec backend php artisan log:clear
	docker compose exec backend php artisan config:clear
	docker compose exec backend php artisan cache:clear

test: ## Run tests inside Sail
	docker compose exec backend php artisan test

shell: ## Open a bash shell in the Sail app container
	docker compose exec backend /bin/bash

tinker: ## Start a Laravel Tinker session
	docker compose exec backend php artisan tinker

bash: ## Run a command inside the container (usage: make bash cmd="ls -la")
	docker compose exec backend /bin/bash -c "$(cmd)"

crud: ## Create a full CRUD stack (usage: make crud name=Post)
	docker compose exec backend php artisan make:crud $(name)

model: ## Generate model + migration + factory + seeder (usage: make model name=Post)
	docker compose exec backend php artisan make:model $(name) -mfs

ini-pull: ## (Recovery) Copy php.ini from container to local docker/8.3/php.ini
	docker compose cp backend:/etc/php/8.3/cli/conf.d/99-sail.ini ./docker/8.3/php.ini

config: ## Generate MySQL and Redis configurations (used by Docker volumes)
	php setup/generate-db-sql.php
	php setup/generate-redis-conf.php
