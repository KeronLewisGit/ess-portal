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

## Phase 2 — Employee master data & HR provisioning

### Schema & data model

31. **`employees` is the master record; `users` is only a login.** A person can
    exist as an employee with no account (`users.employee_id` nullable, now
    with the real FK from Phase 1's deferred constraint, `nullOnDelete`).
32. **`employee_code` is the human-facing unique key** (`EMP0001`), separate
    from the surrogate `id`. Import and letters reference the code, never the
    autoincrement id.
33. **Soft deletes on employees** — separation history must survive for letters
    and payslips already issued. Employees are normally *deactivated*
    (`employment_status = separated`), and hard delete is HR-admin only.
34. **`national_id` and `annual_salary` are encrypted at rest** via Eloquent's
    `encrypted` cast, stored as TEXT (ciphertext is far longer than plaintext).
    Consequence recorded here deliberately: they are **not searchable or
    sortable in SQL**, and **losing `APP_KEY` makes them unrecoverable**. Both
    are also in `$hidden` so they never serialize.
35. **Enums as native DB enums** (`employment_type`, `employment_status`,
    `pay_frequency`) mirroring `App\Enums\*`, matching Phase 1's `users.role`.
36. **`departments.head_employee_id` FK is added after `employees` exists** —
    the two tables reference each other, so the constraint is deferred to the
    employees migration to avoid a circular create order.
37. **Manager is a self-referencing `manager_id`** on employees, `nullOnDelete`.
    No cycle prevention is enforced yet (an org chart that loops is possible);
    flagged for the reporting phase, which is where a hierarchy is walked.
38. **Salary currency defaults per employee** from `ess.defaults.salary_currency`
    rather than being global — subsidiaries or expat contracts may differ.

### Authorisation

39. **Split HR permissions:** *all* HR staff (`hr_officer`, `hr_admin`,
    `super_admin`) may browse and view employees; only HR **admins** may
    create, edit, delete, bulk-deactivate, import, or provision logins. Salary
    and national ID are in the record, so write access is the narrower ring.
40. **Employees may view only their own record**, resolved from the session
    user's `employee_id` — never from a request-supplied id.
41. **Role escalation is blocked at provisioning:** an `hr_admin` can grant
    employee / hr_officer / hr_admin, but **only a `super_admin` can create
    another `super_admin`** (`Role::assignableRoles()`). Without this an HR
    admin could mint a super-admin account it controls. Enforced in the form
    request *and* reflected in the role dropdown.

### Provisioning & invitations

42. **Invitations reuse the password-reset broker.** The "set your password"
    link is a standard reset token — expiring, single-use, already rate
    limited — instead of a bespoke invitation token table.
43. **Provisioned accounts get a 40-char random password** that is never
    disclosed to anyone, plus `must_change_password = true`. The invite link is
    the only usable way in.
44. **`must_change_password` is now enforced** (Phase 1 item 16 closed) by the
    `password.changed` middleware: every route except the change-password
    screen and logout redirects until a new password is set.
45. **Re-provisioning an existing employee re-sends the invitation** rather than
    erroring, so HR can recover a lost invite. Accounts are matched by
    `employee_id`, falling back to work email for pre-existing (Phase 1 demo)
    users.
46. **Invitation mail is queued**, not sent inline, so a slow or down SMTP relay
    can't fail the HR request. Requires a queue worker (`database` driver).

### Bulk import

47. **`maatwebsite/excel` ^3.1 added** — the first dependency beyond Phase 1's
    stack. It handles CSV and XLSX with one code path; hand-rolling XLSX was
    not worth it.
48. **Import is two-step: dry-run preview, then confirm.** The preview reports
    every failing row with its 1-based row number and writes nothing.
49. **All-or-nothing commit.** If *any* row fails validation the whole file is
    rejected — a half-imported payroll file is worse than no import. Errors are
    reported for every bad row at once, not just the first.
50. **Uploads are staged on the `private` disk** under a UUID name between
    preview and commit, then deleted on commit. The confirm step re-validates
    the file and the token is constrained to a UUID (it is interpolated into a
    storage path).
51. **File type is sniffed by the validator** (`mimes:`), not trusted from the
    extension. Size cap 5 MB.
52. **Import resolves departments and managers by code**
    (`department_code`, `manager_employee_code`), since an HR spreadsheet won't
    carry database ids. Unknown codes are row errors, not silent nulls — a
    typo'd manager must not quietly import an employee with no manager.
    Managers are checked in a **second pass** against existing employees *plus*
    the codes in the file, so a manager listed further down the same file is a
    valid forward reference; links are applied after all rows are inserted.
53. **No update-on-import.** A duplicate `employee_code` or work email is an
    error, not an upsert — bulk overwriting existing salary data by accident is
    the more expensive mistake. Duplicates *within* the file are caught too.

### Audit trail

54. **Generic `Auditable` trait + polymorphic `audit_logs`** rather than a
    per-table history, so letters, payslips and templates reuse it unchanged
    in later phases.
55. **Anything in a model's `$hidden` is redacted to `********`** in the audit
    trail (plus `password`/`remember_token` always). The log records *that*
    salary changed, never the values.
56. **Only real changes are logged** — timestamp-only and no-op saves are
    skipped, and force-deletes are distinguished from soft deletes.
57. **Bulk deactivate iterates rows instead of one mass `UPDATE`**, because a
    mass update bypasses model events and would leave no audit trail. Slower,
    but auditable.
58. **Audit logs are append-only and have no UI yet** — a viewer is a Phase 6
    (reporting) deliverable.

### Reference numbers

59. **`DocumentSequenceService` is built now, ahead of its consumers.** Letters
    (Phase 3/4) and payslips (Phase 5) both need gap-free numbers; the counter
    is a `prefix + year` row locked with `SELECT … FOR UPDATE`. Never derived
    from `COUNT(*)` or `MAX(id)` — both race under concurrency.
60. **Format is `PREFIX-YYYY-00001`**, zero-padded to 5 digits, counter resets
    per year.

### Conventions

61. **Business logic lives in `app/Services`** (`EmployeeService`,
    `EmployeeImportService`, `UserProvisioningService`,
    `DocumentSequenceService`); controllers only authorise, validate and
    delegate. Sensitive fields are assigned only in the service layer.
62. **Blank sensitive fields on edit do not wipe stored values.** An empty
    salary or national ID box in the edit form means "leave unchanged" — the
    encrypted values are never re-displayed, so a blank field is the normal
    state, not an instruction to clear.
63. **Demo data:** 6 departments and 26 employees are seeded (idempotently) so
    list, search, filter and pagination have something to work against.
    `EMP0001` is linked to the Phase 1 `employee@example.com` account.

## Phase 3 — Letter requests

### Workflow

64. **Statuses are `draft → submitted → approved | rejected`, plus
    `cancelled`.** A draft is the employee's private scratch space; only
    `submitted` sits in the HR queue.
65. **Single-step approval (confirmed with the client).** Any HR staff —
    officer, admin or super admin — can decide a request. There is no
    officer-recommends/admin-approves chain, which keeps a small HR team from
    deadlocking on one person.
66. **Reference numbers are assigned at submission, not at draft creation**, so
    an abandoned draft never burns a number and the sequence stays gap-free.
    Each letter type carries its own `reference_prefix`, so counters are
    per-type-per-year (`EC-2026-00001`, `BL-2026-00001`).
67. **Rejections must carry a reason** — it is shown to the employee and
    emailed to them. Approvals may carry optional notes.
68. **Employees can withdraw** a request while it is draft or pending;
    once decided it is frozen. Nothing is ever hard-deleted
    (`LetterRequestPolicy::delete()` returns false unconditionally) — history
    is retained for audit, and requests are soft-deleted at most.
69. **Status transitions are guarded in `LetterRequestService`, not just the
    policy.** Policies answer *who* may act; the service answers whether the
    transition is legal at all, so an out-of-order transition throws even if
    reached from a console command or a future API.

### Salary disclosure

70. **Salary on a letter is employee opt-in, HR-admin approved (confirmed with
    the client).** The employee ticks "include my salary"; that request can
    then only be *approved* by an `hr_admin`/`super_admin`, never an
    `hr_officer`.
71. **An HR officer may still *reject* a salary request.** Rejecting discloses
    nothing, and letting officers clear obvious non-starters keeps the queue
    moving.
72. **The salary VALUE is never copied onto the request row**, and is not shown
    on the approval screen. Approval only authorises the value to be read from
    the encrypted employee field at generation time (Phase 4). The approval UI
    shows only *that* the letter will state salary.

### Templates

73. **Letter templates live in the database, not in Blade files**, so HR can
    reword an official letter without a deployment. Bodies use
    `{{ placeholder }}` tokens listed in `LetterType::PLACEHOLDERS` and shown
    in the template editor.
74. **Substitution itself is deferred to Phase 4** (generation). Phase 3 stores
    and validates the template; nothing renders a letter yet.
75. **All HR staff may read templates; only HR admins may write them.**
    Officers need to know what a template says while working the queue, but
    the wording of an official company letter is an admin-level change.
76. **A template that has been used is never deleted** — issued letters must
    keep resolving their type. It is deactivated instead (`is_active`), which
    hides it from the employee's dropdown while leaving history intact. The FK
    is `restrictOnDelete` as a database-level backstop.
77. **Five starter templates are seeded** (employment confirmation, salary
    certificate, bank loan, visa support, experience), idempotently by code.
    The wording is a starting point for HR to edit, not a legal position.

### Rate limiting

78. **The letter-request write endpoints are throttled** at
    `RATE_LIMIT_LETTER_REQUESTS_PER_DAY` (default 10), closing Phase 1 item 17
    for letters. The limiter is keyed **by user id**, falling back to IP only
    for guests — keying by IP alone would penalise a whole office behind one
    NAT. Reads and cancellation are not throttled.

### Notifications

79. **The employee is emailed on approval and rejection**, queued like the
    Phase 2 invitation so a slow relay can't fail the HR request. The email
    carries the outcome and any reason, never the salary value.
80. **A missing work email is skipped silently** rather than failing the
    decision — a recorded HR decision must not roll back because a
    notification could not be addressed.
