.DEFAULT_GOAL := help

COMPOSE   = podman-compose
APP       = $(COMPOSE) exec app
CONSOLE   = $(APP) php bin/console
COMPOSER  = $(APP) composer

help: ## Show this help message
	@grep -E '^[a-zA-Z_-]+:.*##' $(MAKEFILE_LIST) | awk 'BEGIN {FS = ":.*##"}; {printf "  \033[36m%-20s\033[0m %s\n", $$1, $$2}'

# ── Infrastructure ───────────────────────────────────────────

up: ## Start and build containers (detached)
	$(COMPOSE) up -d

up-build: ## Start and build containers (detached)
	$(COMPOSE) up -d --build

down: ## Stop and remove containers
	$(COMPOSE) down

restart: down up ## Restart all containers

shell: ## Open a shell inside the app container
	$(APP) sh

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
	$(APP) php bin/phpunit

test-unit: ## Run unit tests only
	$(APP) php bin/phpunit --testsuite=unit

test-functional: ## Run functional tests only
	$(APP) php bin/phpunit --testsuite=functional

# ── Xdebug ────────────────────────────────────────────────────

xdebug-on: ## Enable Xdebug (no rebuild needed)
	cp docker/xdebug-enabled.ini docker/xdebug.ini
	$(APP) supervisorctl signal USR2 php-fpm
	@echo "✅ Xdebug enabled"

xdebug-off: ## Disable Xdebug (no rebuild needed)
	cp docker/xdebug-disabled.ini docker/xdebug.ini
	$(APP) supervisorctl signal USR2 php-fpm
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
