# Hydrodactyl dev environment — one command.
# Usage: make dev
# For artisan/composer/pnpm/test: use ./bin/run (args work natively)
#   ./bin/run artisan migrate
#   ./bin/run composer require foo/bar
#   ./bin/run test --filter=EggTest
# Or run once: make setup-terminal → then `hydro artisan migrate` from anywhere

REPO_ROOT := $(shell pwd)
COMPOSE   := docker compose -f docker-compose.develop.yml
PANEL     := $(COMPOSE) exec -T -u nginx panel

.PHONY: dev up down stop restart logs shell tinker \
        fe-build fe-watch fe-install \
        lint format backup nuke prune help setup-terminal

setup-terminal: ## add `hydro` shell function to .bashrc/.zshrc (run once)
	@bash bin/setup-terminal

help: ## Show this help
	@awk 'BEGIN {FS = ":.*?## "} /^[a-zA-Z_-]+:.*?## / {printf "  \033[1m%-14s\033[0m %s\n", $$1, $$2}' $(MAKEFILE_LIST)

dev: ## bring up full dev stack (idempotent)
	@bash bin/dev

up: ## docker compose up -d
	$(COMPOSE) up -d

down stop: ## stop stack, keep volumes
	$(COMPOSE) stop

restart: ## restart panel service
	$(COMPOSE) restart panel

logs: ## tail panel logs
	$(COMPOSE) logs -f --tail=100 panel

shell: ## bash into panel container
	$(COMPOSE) exec -u nginx panel /bin/bash

tinker: ## open Laravel tinker
	$(PANEL) php artisan tinker

fe-install: ## install JS deps inside panel container
	$(PANEL) pnpm install --frozen-lockfile

fe-build: ## build frontend (one-shot) inside panel container
	$(PANEL) pnpm run build

fe-watch: ## Vite HMR on host (needs Node 22.13+ locally)
	@test -d node_modules || (echo "[fe-watch] node_modules missing; run: pnpm install" && exit 1)
	pnpm run dev -- --host 0.0.0.0

lint: ## biome check (frontend)
	pnpm exec biome check

format: ## biome lint --write (frontend)
	pnpm exec biome lint --write

backup: ## snapshot srv/ + .env
	tar czf .dev-backup-$(shell date +%Y%m%d-%H%M%S).tar.gz srv .env 2>/dev/null || true

nuke: ## stop + WIPE srv/ + vendor (destructive, full reset)
	$(COMPOSE) down -v
	docker run --rm -v '$(PWD)/srv:/srv' alpine sh -c 'rm -rf /srv/*' 2>/dev/null || true
	rm -rf srv vendor 2>/dev/null || true
	@echo "Run 'make dev' to rebuild from scratch."

prune: ## docker system prune (frees disk, keeps volumes)
	docker system prune -f
