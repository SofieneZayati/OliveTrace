# Shared base verification

Verified on 6 October 2026 on Sofiene's Windows computer.

## Environment

- PHP 8.4.26 with Laravel/MySQL extensions and Composer 2.10.3.
- Laravel 12.69.3, Breeze 2.4.2 (Blade).
- Laravel Doctrine integration 3.3.3, ORM 3.7.4 and DBAL 4.5.0.
- MySQL Community 8.4.9 on 127.0.0.1:3306.
- Git 2.46.1, Node 24.20.0, npm 11.6.1.

## Checks completed

| Check | Result |
| --- | --- |
| Composer install, strict manifest validation, platform requirements | Passed |
| Composer audit | No reported security advisories |
| npm install and frontend build | Passed |
| npm audit | No reported vulnerabilities |
| Laravel start and HTTP pages | Passed at 127.0.0.1:8000 |
| MySQL connection, migrations and development seeder | Passed |
| Default SQLite test suite | 44 passed, 184 assertions |
| Same suite against separate MySQL testing database | 44 passed, 184 assertions |
| Pint formatting check | Passed |
| Route cache and Blade view cache | Passed; caches cleared afterward |
| Doctrine connection/mapping health check | Passed; 0 production business entities |
| Test-only Doctrine persist/read/update/delete round trip | Passed on SQLite and MySQL |
| Clean local Git clone: dependencies, assets, fresh MySQL migration/seed, tests | Passed |
| Git diff whitespace check | Passed |
| Real local credentials/key patterns in Git history | None found |
| .env, vendor, node_modules, builds and private setup files tracked | None |

The clean clone used a newly created, separate test schema; it did not reset
the development database. The test bootstrap also rejects non-testing database
names before migration tests run.

## Account and browser checks

Automated checks cover registration, login, logout, password confirmation,
password update, reset-token generation and password reset, profile edit/delete,
every role's dashboard, all admin user routes for every non-admin role, input
validation, filtering, role/status changes, activation/deactivation, self-admin
safeguards, final-admin protection, restricted foreign-key deletion and
development-only/idempotent seeding.

Browser checks cover the Home/Front Office layout, registration as consumer,
admin login/dashboard, admin user list/search/role filter/edit form, consumer
dashboard/profile, logout, consumer denial at `/admin/users` (403), and reset-link
request generation. No browser console warnings/errors were recorded during
those flows. Temporary browser-test data were removed afterward.

Password reset uses the **local log mailer**. The generated notification was
verified in the private application log, and the full token reset was tested
automatically. SMTP delivery was not configured or claimed as tested.

## Scope verified

The development database contains `users`, `password_reset_tokens`, `sessions`,
`cache`, `cache_locks`, `jobs`, `job_batches`, `failed_jobs` and `migrations`.
There are exactly six development users after cleanup. There are no business
entities, module migrations/controllers/pages, QR features or AI integrations.

GitHub Actions is enabled and configured to repeat core checks with PHP 8.3,
Node 24 and MySQL 8.4, including an optional manual run. The checks above were
performed locally before pushing; remote run status is available in the
repository's Actions tab.
