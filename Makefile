.DEFAULT_GOAL := help

COMPOSE   = podman-compose
# PHP commands run as www-data, the same user as php-fpm, so the cache they write stays usable by the web server.
APP       = $(COMPOSE) exec -u www-data app
APP_ROOT  = $(COMPOSE) exec app
CONSOLE   = $(APP) php bin/console
# vendor/ belongs to root, so Composer runs as root
COMPOSER  = $(APP_ROOT) composer
FIX_CACHE = $(APP_ROOT) sh -c 'chown -R www-data:www-data "$$APP_CACHE_DIR" "$$APP_LOG_DIR"'

help: ## Show this help message
	@grep -E '^[a-zA-Z_-]+:.*##' $(MAKEFILE_LIST) | awk 'BEGIN {FS = ":.*##"}; {printf "  \033[36m%-20s\033[0m %s\n", $$1, $$2}'

# ── Infrastructure ───────────────────────────────────────────

up: ## Start containers (detached)
	$(COMPOSE) up -d

up-build: ## Build images and start containers (detached)
	$(COMPOSE) up -d --build

down: ## Stop and remove containers
	$(COMPOSE) down

down-volumes: ## Stop and remove containers and named volumes (vendor); ./db/data is kept
	$(COMPOSE) down -v

build-app: ## Build the app image
	$(COMPOSE) build app

build-app-no-cache: ## Build the app image without cached layers
	$(COMPOSE) build --no-cache app

up-app: ## Start (or recreate) only the app container (detached)
	$(COMPOSE) up -d app

restart: down up ## Restart all containers

shell: ## Open a shell inside the app container (as www-data)
	$(APP) sh

shell-root: ## Open a root shell inside the app container
	$(APP_ROOT) sh

logs: ## Follow the Symfony dev log inside the app container
	$(APP) sh -c 'tail -f "$$APP_LOG_DIR/dev.log"'

# ── Dependencies & Database ──────────────────────────────────

composer-install: ## Install Composer dependencies inside the app container
	$(COMPOSER) install
	$(FIX_CACHE)

migrate: ## Run Doctrine migrations inside the app container
	$(CONSOLE) doctrine:migrations:migrate

create-reader: ## Create a new reader (asks for nickname and full name)
	$(CONSOLE) app:reader:create

# ── Test database ─────────────────────────────────────────────
# Always runs with --env=test, so it only touches the "_test" database.

test-db: ## (Re)create the test database, run migrations and load fixtures
	$(CONSOLE) --env=test doctrine:database:drop --force --if-exists
	$(CONSOLE) --env=test doctrine:database:create
	$(CONSOLE) --env=test doctrine:migrations:migrate --no-interaction
	$(CONSOLE) --env=test doctrine:fixtures:load --no-interaction

test-fixtures: ## Reload fixtures into the test database (purges it first)
	$(CONSOLE) --env=test doctrine:fixtures:load --no-interaction

# ── Symfony Console ───────────────────────────────────────────

cache-clear: ## Clear Symfony cache
	$(CONSOLE) cache:clear

cache-warmup: ## Warm up Symfony cache
	$(CONSOLE) cache:warmup

routes: ## List all registered routes
	$(CONSOLE) debug:router

services: ## List all registered services
	$(CONSOLE) debug:container

# ── Tests ─────────────────────────────────────────────────────

test: ## Run all tests
	$(APP) php bin/phpunit --cache-directory=/tmp/phpunit-cache

test-integration: ## Run integration tests only (repositories against the test database)
	$(APP) php bin/phpunit --cache-directory=/tmp/phpunit-cache --testsuite=integration

test-functional: ## Run functional tests only (HTTP requests through the whole app)
	$(APP) php bin/phpunit --cache-directory=/tmp/phpunit-cache --testsuite=functional

# ── Xdebug ────────────────────────────────────────────────────

xdebug-on: ## Enable Xdebug (no rebuild needed)
	cp docker/xdebug-enabled.ini docker/xdebug.ini
	$(APP_ROOT) supervisorctl signal USR2 php-fpm
	@echo "✅ Xdebug enabled"

xdebug-off: ## Disable Xdebug (no rebuild needed)
	cp docker/xdebug-disabled.ini docker/xdebug.ini
	$(APP_ROOT) supervisorctl signal USR2 php-fpm
	@echo "✅ Xdebug disabled"

xdebug-toggle: ## Toggle Xdebug on/off
	@if grep -q "zend_extension" docker/xdebug.ini 2>/dev/null; then \
		$(MAKE) xdebug-off; \
	else \
		$(MAKE) xdebug-on; \
	fi

xdebug-status: ## Show current Xdebug status
	@if grep -q "zend_extension" docker/xdebug.ini 2>/dev/null; then \
		echo "🟢 Xdebug is ENABLED"; \
	else \
		echo "🔴 Xdebug is DISABLED"; \
	fi
