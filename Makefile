.PHONY: help up down restart migrate seed test serve dev install setup shell run

help: ## Show this help
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-15s\033[0m %s\n", $$1, $$2}'

run: ## Run the full setup and start the server. Use 'make run i=1' to include installations.
	php run.php $(if $(i),-i)

up: ## Start docker containers
	docker compose up -d

down: ## Stop docker containers
	docker compose down

restart: down up ## Restart docker containers

migrate: ## Run database migrations
	php artisan migrate

seed: ## Run database seeders
	php artisan db:seed

test: ## Run tests
	php artisan test

serve: ## Start the PHP development server
	php artisan serve

dev: ## Start the Vite development server
	npm run dev

install: ## Install composer and npm dependencies
	composer install && npm install

setup: install config ## Initial project setup
	cp -n .env.example .env || true
	php artisan key:generate
	php artisan migrate
	@echo "Setup complete. Run 'make up' to start services and 'make serve' for the app."

config: ## Generate MySQL and Redis configurations
	php setup/generate-db-sql.php
	php setup/generate-redis-conf.php

shell: ## Open a bash shell
	bash
