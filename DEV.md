## Dev Environment

One command brings up the full hydrodactyl stack:

```
make dev
```

### What you get

| Service    | Access                                              |
|------------|-----------------------------------------------------|
| Panel      | http://localhost:${PANEL_PORT} (default 3000)       |
| Mailpit UI | http://localhost:8025                               |
| MinIO UI   | http://localhost:9001  (minioadmin / minioadmin)    |
| Elytra     | port 8080 (daemon), 2022 (sftp)                    |
| Vite HMR   | http://localhost:5173                               |

Login: **admin / admin** (root admin user).

### Dev tooling built in

- **Laravel Telescope** — `/telescope` on the panel domain. Debug queries, jobs, mail, cache, etc.
- **Laravel Debugbar** — injected into every HTML page. DB queries, routes, memory, timeline.
- **Clockwork** — browser extension (Chrome/Firefox) for request timeline + log + DB panel.
- **Redux DevTools** — browser extension. easy-peasy store is instrumented in dev mode.
- **React DevTools** — browser extension.

### Auto-configured daemon

On first seed, `DevSetupSeeder` creates:

- Location `dev` / "Development"
- Node `hydrodactyl-dev` pointing at `elytra:8080` (daemon type: Elytra)
- Allocations on ports 25565–25575
- Daemon config written to `srv/pterodactyl/config/config.yml` (shared volume with elytra)

Elytra reads this config and shows UP in the panel admin.

### Quick commands

```
make dev        # Bring up full stack (idempotent)
make logs       # Tail panel logs
make shell      # Shell into panel container
make artisan    # e.g. make artisan CMD='migrate:status'
make fe-build   # Build frontend (inside container)
make tinker     # Laravel tinker
make stop       # Stop stack, keep data
make nuke       # Full reset: stop + delete srv/ + vendor cache
```

### Filesystem

- Panel code is bind-mounted from the repo root → changes are live.
- DB data persists in `srv/database/`.
- Daemon config lives in `srv/pterodactyl/config/` (shared with Elytra container).
- Frontend HMR: auto-started inside the container (`pnpm run dev --host`). Hot reload at http://localhost:5173.

### Troubleshooting

**"Port already in use"** — `bin/dev` auto-increments PANEL_PORT. Or set `PANEL_PORT=4000 make dev`.

**Elytra shows OFFLINE** — run `docker compose -f docker-compose.develop.yml restart elytra`.

**Telescope returns 404** — ensure `APP_ENV=local` or `TELESCOPE_ENABLED=true` in `.env`.

**Debugbar not showing** — ensure `APP_DEBUG=true` in `.env` and check browser console for errors.
