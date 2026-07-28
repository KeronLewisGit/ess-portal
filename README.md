# ESS Portal

**Version 0.4.0** (Phase 4 — Letter generation)

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

Self-registration is disabled — accounts are provisioned by HR from an
employee record (**Employees → view → Create login**), which emails a
set-password invitation and forces a password change on first login.

Seeding also creates six departments and 26 employees (`EMP0001` is linked to
`employee@example.com`); invitation email lands in Mailpit.

## What works so far

- **Phase 1** — auth, roles, policies, company settings, health checks
- **Phase 2** — employee master data and HR provisioning:
  - Departments CRUD (`/hr/departments`)
  - Employees CRUD with search, department/status filters, pagination and
    bulk deactivate (`/hr/employees`)
  - CSV/XLSX bulk import with a **dry-run preview** — a row-by-row validation
    report; nothing is written unless every row passes
    (`/hr/employees/import`, downloadable column template)
  - Login provisioning: creates the account, emails a set-password invite,
    forces a password change on first sign-in
  - Audit trail of every create/update/delete, with sensitive fields redacted
  - Race-safe reference-number generator (row-locked counters, used by
    letters and payslips in later phases)
- **Phase 3** — letter requests:
  - HR-editable letter templates with `{{ placeholder }}` tokens
    (`/hr/letter-types`) — five starter templates are seeded
  - Employees request a letter, save drafts, and track status (`/requests`)
  - HR approval queue with approve/reject and a required rejection reason
    (`/hr/approvals`); the employee is emailed the outcome
  - Salary on a letter is **employee opt-in and HR-admin approved** — an
    HR officer can reject such a request but cannot approve it
  - Reference numbers assigned at submission, per letter type and year
    (`EC-2026-00001`), and request writes are rate limited per day
- **Phase 4** — letter generation:
  - Approving a request queues a job that renders the PDF onto the private
    disk and emails the employee that it's ready (**needs a queue worker** —
    without one, requests stay at "approved")
  - Downloads require **both** a short-lived signed URL and a policy pass;
    the stored SHA-256 is re-checked on every download
  - Letterhead logo and signature uploaded at `/admin/settings`, stored
    privately and embedded into PDFs as data URIs (never served over HTTP)
  - Public verification at `/verify/{token}` — discloses only reference,
    employee **initials**, letter type and issue date
  - HR admins can revoke an issued letter; verification then reports it as
    revoked and the employee can no longer download it

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
- Encrypted at rest: `employees.national_id` and `employees.annual_salary`
  (Eloquent `encrypted` cast — **losing `APP_KEY` makes them unrecoverable**)
- Audit trail: `App\Models\Concerns\Auditable` writes to `audit_logs`;
  anything in a model's `$hidden` is redacted before it is stored
- Business logic lives in `app/Services` (employee writes, import, user
  provisioning, reference numbers); controllers stay thin
- Reference numbers: `App\Services\DocumentSequenceService` (`SELECT … FOR
  UPDATE` on a per-prefix/year counter — never `COUNT(*)`/`MAX(id)`)
- Status workflows live in services, not controllers —
  `App\Services\LetterRequestService` owns every letter transition, so
  policies answer *who* may act and the service answers whether the
  transition is legal at all
- Rate limiters are named and env-driven, defined in `AppServiceProvider`
  (`throttle:letter-requests`, `throttle:verification`), keyed by user id
  rather than IP where a user is signed in
- Issued letters are immutable: `issued_letters` stores the PDF path, a
  SHA-256 of the bytes, and a snapshot of the facts as at issue time (the
  salary is deliberately NOT in the snapshot — it exists only in the PDF)
- `/verify/{token}` is the only unauthenticated application route

See `ASSUMPTIONS.md` for every default chosen during the build.
