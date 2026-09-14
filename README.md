# LMS

Laravel 13 · Inertia 2 + React 19 + TypeScript · Postgres 17. All local, no paid services.

See [PLAN.md](PLAN.md) for scope and phases, [PROGRESS.md](PROGRESS.md) for status.

## Run it

Postgres does not start on boot. Start it once per reboot:

```sh
"/Applications/Postgres.app/Contents/Versions/17/bin/pg_ctl" \
  -D "$HOME/Library/Application Support/Postgres/var-17" \
  -l "$HOME/Library/Application Support/Postgres/var-17/server.log" start
```

(Or open Postgres.app once — it adopts the same data directory and gives you a menubar toggle.)

Then:

```sh
composer run dev     # serve :8000 + vite :5173 + queue + logs
```

Open http://localhost:8000

| Login | Password |
|---|---|
| `admin@lms.test` | `password` |
| `student@lms.test` | `password` |

## Checks

```sh
./vendor/bin/pest      # 28 tests
./vendor/bin/pint      # format
npm run build          # production assets
```

## Reset the database

```sh
php artisan migrate:fresh --seed
```

## Notes

- Two databases: `lms` (app) and `lms_test` (tests, wiped per run).
- `php` is MAMP's 8.4.17 — it already has `pdo_pgsql`.
- Postgres CLI tools are not on `PATH`. Add if you want `psql`:
  `export PATH="/Applications/Postgres.app/Contents/Versions/17/bin:$PATH"`
