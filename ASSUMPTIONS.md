# Assumptions & decisions log

Every default chosen without explicit sign-off, with rationale. Raised in the
phase summaries per the build spec.

## Phase 1 — Foundation

### Environment & toolchain

1. **Project location:** `C:\Users\keron.lewis\ess-portal` (fixed decision from
   the kickoff). App slug: `ess-portal`.
2. **Docker-based dev environment.** The host has no PHP/Composer/MySQL, so all
   PHP tooling runs in containers (`docker-compose.yml`: PHP-FPM 8.3 + nginx
   + MySQL 8 + Mailpit). Node/npm/Vite run on the host, matching the
   "no Node on the server" constraint.
3. **Laravel version:** `composer create-project laravel/laravel` resolved
   **Laravel Framework 13.23.0** (skeleton requires PHP `^8.3`). The spec's
   "PHP 8.2+" floor is therefore superseded by the framework's own `^8.3`
   requirement; the runtime targets PHP 8.3 and avoids 8.4-only features.
   `composer.json` pins `config.platform.php = 8.3.0` so dependency resolution
   always matches the 8.3 runtime (the official composer image runs PHP 8.4
   and initially produced an 8.4-only lock file, which was re-resolved).
4. **Testing framework: PHPUnit** (v12, as shipped by the Laravel 13 skeleton +
   Breeze). The spec prefers Pest but explicitly allows PHPUnit "if Pest causes
   friction"; staying with the skeleton default avoids an extra dependency.
5. **Dev-only container entrypoint** relaxes permissions on `storage/` and
   `bootstrap/cache` at startup because Windows bind mounts appear root-owned
   inside containers. Never used in production.

### Placeholder configuration (env-driven, editable in UI)

6. **Company name:** `Acme Manufacturing Ltd` — placeholder, set via
   `COMPANY_NAME` and editable at `/admin/settings`.
7. **Company address:** `1 Industrial Park Way, Springfield` — placeholder.
8. **Salary currency:** `USD`. **Timezone:** `UTC` (`APP_TIMEZONE`).
9. **HR contact email:** `hr@example.com` — placeholder.
10. **Initial version:** `0.1.0` in `composer.json` and `README.md`;
    incremented after each meaningful change per project convention.

### Auth & roles

11. **Self-registration disabled** (routes, controller, view and test removed).
    The spec says accounts are provisioned by HR with invitation emails
    (Phase 2); an open `/register` endpoint would violate that model. Demo
    accounts are seeded instead.
12. **Deactivated accounts can't log in:** `is_active = true` is merged into
    the login credential check, so an inactive account behaves exactly like a
    wrong password (no account-state information leak).
13. **`users.employee_id` is created now** (nullable, indexed) so policies can
    scope by it from day one, but the FK constraint is deferred to Phase 2 when
    the `employees` table exists.
14. **`users.role` is a native DB enum** matching `App\Enums\Role`. Works on
    MySQL 8 and MariaDB 10.6+; no CHECK constraints used.
15. **Role/`is_active`/`must_change_password` are NOT mass assignable** — they
    are set only via explicit `forceFill` in trusted flows (seeder, future HR
    provisioning). Spec's "explicit `$fillable`" rule is applied with classic
    `$fillable` arrays on every model.
16. **`must_change_password` column exists but is not yet enforced** — the
    forced-password-change middleware is part of the HR provisioning flow
    (Phase 2) per the spec ("accounts created by HR").
17. **Login rate limiting**: Breeze's built-in limiter (5/min per email+IP) is
    in place; the env-configurable limits (`RATE_LIMIT_*`) are wired into
    `config/ess.php` and will be applied to the letter/payslip endpoints in
    their phases (endpoints don't exist yet).

### Security posture set in Phase 1

18. **CSP includes `'unsafe-eval'` for scripts** because Alpine.js (bundled by
    Breeze) evaluates expressions via `new Function()`. Recorded for Phase 7
    hardening (Alpine CSP build would remove it). Styles allow
    `'unsafe-inline'` + `fonts.bunny.net` to match the Breeze layouts.
19. **Private disk** (`storage/app/private`) configured with `serve => false`
    and `throw => true`; the skeleton's default `local` disk also points there
    but the app will use the explicit `private` disk for all sensitive
    documents. Nothing under it is ever `storage:link`ed.
20. **Placeholder models** `Employee`, `LetterRequest`, `Payslip` exist only so
    the three policies can be registered now; their schemas arrive in Phases
    2/3/5. `Employee` already hides `annual_salary` and `national_id` from
    serialization.
21. **Policy defaults are conservative:** unknown abilities deny; employees
    can only ever match records via their own `employee_id`; HR area gated by
    both route middleware (`role:`) and gates, with record-level policy checks
    to follow in each phase (belt and braces).
22. **SECURITY.md is deferred to Phase 7** (hardening) when the full list of
    implemented controls can be documented accurately.

### Settings module

23. **Settings are strings** (key/value, TEXT). Complex letterhead options can
    serialize to JSON later if needed. The editable key list is whitelisted in
    `SettingsController::FIELDS`; unknown keys in the payload are discarded.
24. **Seeding is fill-if-missing** (`firstOrCreate`), so re-running seeders
    never overwrites values edited in the UI.
25. **Logo/signature are text paths for now** — file uploads belong to the
    letter-generation phase (Phase 4).
26. **Per-key cache** via `Cache::rememberForever`, invalidated by model
    `saved`/`deleted` events.

### Misc

27. **Health checks** hook the built-in `/up` route via the `DiagnosingHealth`
    event: DB connectivity (`select 1`), private-disk write/read/delete probe,
    and queue driver must be `database` (with `jobs` table present) or `sync`.
28. **No packages beyond the spec's stack** were added in Phase 1 (Breeze +
    skeleton dev tools only: Pint, PHPUnit, Collision, Faker, Pail and the
    skeleton's bundled `laravel/pao`; dompdf/excel/fpdi arrive in their
    phases).
29. **The `/` route redirects** to the dashboard (or login) — a portal has no
    public landing page; the default welcome view was removed.
30. **MySQL root/dev passwords in `docker-compose.yml`** (`secret`/`root`) are
    local-only conveniences; production credentials come from the host
    environment.
