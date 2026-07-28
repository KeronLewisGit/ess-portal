# ESS Portal

**Version 0.1.0** (Phase 1 — Foundation)

Employee Self-Service portal for job letters and payslips, built with
**Laravel Framework 13.23.0** (PHP 8.3, MySQL 8, Breeze Blade + Tailwind +
Alpine.js). This is the actual installed framework version — check
`composer.lock` for the authoritative number after any update.

## Prerequisites (local development)

- Docker Desktop (runs PHP-FPM 8.3, nginx, MySQL 8, Mailpit — no PHP,
  Composer or MySQL needed on the host)
- Node.js 20+ and npm (frontend assets are built on the host; no Node in
  containers and none on the production server)
- Git

## Local setup (under ten commands)

```bash
git clone <repo-url> ess-portal && cd ess-portal
cp .env.example .env
docker compose up -d --build
docker compose exec app composer install
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate --seed
npm install && npm run build
```

Then open:

- App: <http://localhost:8080> (health check at `/up`)
- Mailpit (captured email): <http://localhost:8025>
- MySQL from host tools: `127.0.0.1:33060` (user `ess`, password `secret`)

Day-to-day commands run through the app container, e.g.:

```bash
docker compose exec app php artisan migrate
docker compose exec app composer test    # test suite
docker compose exec app composer lint    # Laravel Pint
docker compose exec app composer fresh   # migrate:fresh --seed
```

For frontend work use `npm run dev` on the host (Vite dev server with HMR).

### Without Docker

The codebase also runs on plain `php artisan serve` with a local PHP 8.3+,
Composer and MySQL 8 / MariaDB 10.6+ — point `DB_HOST`/`MAIL_HOST` in `.env`
at your local services. (The Docker stack is the supported dev path.)

## Demo accounts

Seeded by `php artisan db:seed` (all with password `password`):

| Role        | Email                    |
| ----------- | ------------------------ |
| Employee    | employee@example.com     |
| HR Officer  | hr.officer@example.com   |
| HR Admin    | hr.admin@example.com     |
| Super Admin | super.admin@example.com  |

Self-registration is disabled — accounts are provisioned by HR (Phase 2).

## Environment variables that MUST change for production

See the fully commented `.env.example`. In short:

| Key | Production value |
| --- | --- |
| `APP_ENV` | `production` |
| `APP_DEBUG` | `false` |
| `APP_KEY` | freshly generated, kept secret (encrypts national IDs / salaries) |
| `APP_URL` | real HTTPS URL |
| `DB_*` | production database credentials |
| `SESSION_SECURE_COOKIE` | `true` (HTTPS only) |
| `MAIL_*` | real SMTP relay + real from address |
| `COMPANY_NAME` / `COMPANY_ADDRESS` / `HR_CONTACT_EMAIL` / `SALARY_CURRENCY` | your real company details |
| `LOG_LEVEL` | `error` or `warning` |

Frontend assets are built locally (`npm run build`) and the `public/build`
directory is deployed — **no Node.js on the server**. Queues use the
`database` driver with a persistent worker or the cron fallback
(`* * * * * php artisan queue:work --stop-when-empty --max-time=55`); full
deployment guides arrive in the hardening phase.

## Project structure notes

- Roles: `App\Enums\Role` (`employee`, `hr_officer`, `hr_admin`, `super_admin`)
- Authorisation: policies in `app/Policies` + `role:` route middleware +
  `access-hr-area` / `manage-settings` gates
- Sensitive documents: the `private` disk (`storage/app/private`), never
  symlinked into `public/`
- Editable company settings: `settings` table, cached via
  `App\Models\Setting::get()`, UI at `/admin/settings` (super admin)
- Health: `/up` verifies DB connectivity, private-disk writability and queue
  configuration (`app/Listeners/EnsureApplicationIsHealthy.php`)

See `ASSUMPTIONS.md` for every default chosen during the build.
