SAIL = ./vendor/bin/sail

.PHONY: up down restart install artisan migrate fresh seed test pint pint-test phpstan tinker shell logs ps

up: ## Start containers in the background
	$(SAIL) up -d

down: ## Stop containers
	$(SAIL) down

restart: down up ## Restart containers

install: ## Install PHP dependencies
	$(SAIL) composer install

artisan: ## Run an artisan command, e.g. make artisan cmd="route:list"
	$(SAIL) artisan $(cmd)

migrate: ## Run pending migrations
	$(SAIL) artisan migrate

fresh: ## Drop all tables, re-migrate and seed
	$(SAIL) artisan migrate:fresh --seed

seed: ## Run database seeders
	$(SAIL) artisan db:seed

test: ## Run the test suite
	$(SAIL) artisan test

pint: ## Fix code style
	$(SAIL) pint

pint-test: ## Check code style without fixing
	$(SAIL) pint --test

phpstan: ## Run static analysis
	$(SAIL) exec -T laravel.test ./vendor/bin/phpstan analyse --memory-limit=1G

tinker: ## Open a tinker REPL
	$(SAIL) artisan tinker

shell: ## Open a shell inside the app container
	$(SAIL) shell

logs: ## Tail container logs
	$(SAIL) logs -f

ps: ## Show container status
	$(SAIL) ps
