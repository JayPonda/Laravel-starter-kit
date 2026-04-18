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
	$(SAIL) artisan migrate

fresh: ## Freshly migrate and seed the database
	$(SAIL) artisan migrate:fresh --seed

seed: ## Run database seeders inside Sail
	$(SAIL) artisan db:seed

queue: ## Start the Sail queue worker
	$(SAIL) artisan queue:work

test: ## Run tests inside Sail
	$(SAIL) test

shell: ## Open a bash shell in the Sail app container
	$(SAIL) shell

crud: ## Create a full CRUD stack (usage: make crud name=Post)
	$(SAIL) artisan make:crud $(name)

config: ## Generate MySQL and Redis configurations (used by Docker volumes)
	php setup/generate-db-sql.php
	php setup/generate-redis-conf.php
