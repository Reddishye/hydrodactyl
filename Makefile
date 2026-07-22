# Hydrodactyl dev environment — one command.
# Usage: make dev

REPO_ROOT := $(shell pwd)
COMPOSE   := docker compose -f docker-compose.develop.yml
PANEL     := $(COMPOSE) exec -T -u nginx panel

.PHONY: dev up down stop restart logs shell artisan tinker \
        composer pnpm npm fe-build fe-watch fe-install test test-unit \
        lint format backup nuke prune help

help: ## Show this help.
	@awk 'BEGIN {FS = ":.*?## "} /^[a-zA-Z_-]+:.*?## / {printf "  \033[1m%-14s\033[0m %s\n", $$1, $$2}' $(MAKEFILE_LIST)

dev: ## (alias) bring up full dev stack
	@bash bin/dev

up: ## docker compose up -d (no build, no first-run checks)
	$(COMPOSE) up -d

down stop: ## stop stack, keep volumes
	$(COMPOSE) stop

restart: ## restart panel service
	$(COMPOSE) restart panel

logs: ## tail panel logs
	$(COMPOSE) logs -f --tail=100 panel

shell: ## bash into panel container
	$(COMPOSE) exec -u nginx panel /bin/bash

artisan: ## run `php artisan ...` in panel container (use CMD='...')
	$(PANEL) php artisan $(CMD)

tinker: ## open Laravel tinker
	$(PANEL) php artisan tinker

composer: ## run composer in panel container (use CMD='...')
	$(PANEL) composer $(CMD)

pnpm npm: ## run pnpm in panel container (use CMD='...')
	$(PANEL) pnpm $(CMD)

fe-install: ## install JS deps inside the panel container
	$(PANEL) pnpm install --frozen-lockfile

fe-build: ## build frontend (one-shot) inside the panel container
	$(PANEL) pnpm run build

fe-watch: ## Vite HMR on host (requires Node 22.13+ locally)
	@test -d node_modules || (echo "[fe-watch] node_modules missing; run: pnpm install" && exit 1)
	pnpm run dev -- --host 0.0.0.0

test: ## run full PHPUnit suite
	$(PANEL) php vendor/bin/phpunit

test-unit: ## run unit tests only
	$(PANEL) php vendor/bin/phpunit --testsuite=Unit

lint: ## biome check (frontend)
	pnpm exec biome check

format: ## biome lint --write (frontend)
	pnpm exec biome lint --write

backup: ## snapshot srv/ + .env to .dev-backup-<ts>.tar.gz
	tar czf .dev-backup-$(shell date +%Y%m%d-%H%M%S).tar.gz srv .env 2>/dev/null || true

nuke: ## stop stack and WIPE srv/ + vendor cache (destructive)
	$(COMPOSE) down
	rm -rf srv vendor
	@echo "Run 'make dev' to rebuild from scratch."

prune: ## docker system prune (frees disk, keeps volumes)
	docker system prune -f
