# OliveTrace

OliveTrace is a university web project for tracing Tunisian olive oil from farm
to consumer. The team is Sofiene, Mariem, Oussema, Hana and Aymen.

**This repository contains the shared foundation and all five team modules.**
Authentication, roles, common user management, layouts and infrastructure are
shared work; they do not count as an individual student's module.

## Included

- Laravel 12, MySQL and Eloquent for all persistence.
- Breeze with Blade: register, login, logout, password reset and account profile.
- Six roles, active/inactive accounts, protected routes and a reusable role middleware.
- Admin user list, search, role/status filters, details, edit, role change,
  activation/deactivation and deletion of unlinked accounts.
- Front Office and Back Office layouts, shared navigation, alerts, validation
  errors, buttons, cards and a simple dashboard.
- Development accounts, automated tests and a GitHub Actions check workflow.

- Sofiene's Eloquent ProducerProfile / Farm relationship, ownership-protected CRUD,
  validated forms, private logo uploads, admin correction/moderation, demo factories
  and seeders, a public origin card and an advisory sustainability AI integration.

The integrated modules are Sofiene's Producer/Farm, Mariem's Harvest/Mill/Oil Lot,
Oussema's Laboratory/Certification, Hana's Product/Distribution, and Aymen's
Consumer Traceability/Feedback/Complaints. See
[Sofiene's implementation and integration contracts](docs/sofiene-production.md).
The farm sustainability assistant supports Gemini, OpenAI and local Ollama.
Normal CRUD works without an AI provider. Credentials stay in your local `.env`.
Gemini setup and demonstration steps are in [the farm AI guide](docs/farm-ai.md).
Certification and product integration rules are in [the module workflow guide](docs/module-workflows.md).

## Software required

| Software | Requirement / tested version |
| --- | --- |
| PHP | **8.3+**; PHP 8.4 recommended, tested with 8.4.26 |
| Laravel | **12.x**; locked to 12.69.3 |
| Composer | 2.x; tested with 2.10.3 |
| MySQL | MySQL 8.0+; 8.4 LTS recommended, tested with 8.4.9 |
| Node.js | Node 22.12+ or Node 24 LTS; tested with 24.20.0 |
| npm | 10+; tested with 11.6.1 |
| Git | A working Git installation; tested with 2.46.1 |

Laravel 12 itself supports PHP 8.2. This project keeps **PHP 8.3+** as its
shared team baseline after the Eloquent refactor. The lock file targets
PHP 8.3 so PHP 8.3 and 8.4 developers install the same dependency versions.

PHP must enable Ctype, cURL, DOM, Fileinfo, Filter, Hash, Mbstring, OpenSSL, PCRE,
PDO, Session, Tokenizer, XML and **pdo_mysql**. Enable Zip for Composer archive
installation and pdo_sqlite for the default in-memory test suite. Intl is also
available on the prepared Windows runtime. Check with `php -m` and `php --ini`.

## Clone and install

Use PowerShell on Windows. If npm.ps1 is blocked by your execution policy, use
`npm.cmd` as shown; no system security-policy change is needed. On macOS/Linux,
use `npm` and `cp .env.example .env`.

```powershell
git clone https://github.com/SofieneZayati/OliveTrace.git olivetrace
cd olivetrace
composer install
npm.cmd install
Copy-Item .env.example .env
php artisan key:generate
```

Start MySQL, sign in with your own local administrative credentials, and create
the development database:

```powershell
mysql -u root -p
```

At the MySQL prompt:

```sql
CREATE DATABASE IF NOT EXISTS olivetrace
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
EXIT;
```

Edit your **local `.env`** to match your MySQL installation:

```dotenv
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=olivetrace
DB_USERNAME=root
DB_PASSWORD=
```

These are placeholders/defaults, not shared credentials. Use your own username
and password. Then finish setup:

```powershell
php artisan migrate --seed
npm.cmd run build
php artisan olivetrace:check-database
php artisan serve
```

Open **http://localhost:8000**. The database check verifies the connection and
required tables without changing data. Keep the server terminal open while using the app.

For live frontend changes, run `npm.cmd run dev` in a second terminal. Otherwise
rebuild with `npm.cmd run build`. `composer run dev` starts the Laravel server;
this deliberately avoids Unix-only process/log tooling on Windows.

For an existing clone after pulling changes: `composer install`, `npm.cmd ci`,
`php artisan migrate`, and `npm.cmd run build`. Run `php artisan optimize:clear`
if old cached configuration or views cause confusion. Commit both lock files;
use `npm ci` in CI or for a reproducible clean install.

### Sofiene's prepared Windows computer

The isolated PHP/Composer/MySQL installation can be activated from the current
project directory without changing XAMPP or unrelated projects:

```powershell
. .\scripts\Use-OliveTrace.ps1
.\scripts\Start-LocalMySql.ps1
php artisan serve
```

Activation is needed in every new terminal if the system PHP still points to
XAMPP 8.2. Full paths, verified extensions and start/stop commands are in
[Windows environment notes](docs/windows-environment.md). These optional helpers
are specific to that machine; other developers can use their existing tools.

## Development accounts

**DEVELOPMENT ONLY.** Run `php artisan migrate --seed` in `APP_ENV=local`.
All six accounts use **`OliveTrace123!`**. This is a public demo password, never a
production credential. The seeder refuses other environments except testing.
Rerunning it restores these six demo accounts and their password/status; it does
not delete other users.

| Email | Role |
| --- | --- |
| admin@test.com | admin |
| producer@test.com | producer |
| miller@test.com | miller |
| lab@test.com | laboratory |
| distributor@test.com | distributor |
| consumer@test.com | consumer |

Public registration always creates an **active consumer**. Only an administrator
can assign a different role. A submitted `role` or `is_active` field cannot
escalate public registration or profile privileges.

## Roles and shared screens

Guests see Home, Login and Register. Consumers use the Front Office with Home,
Dashboard, Profile and Logout. Admins, producers, millers, laboratories and
distributors use the Back Office dashboard, Profile and Logout. Admins also see
Users and can open `/admin/dashboard` and `/admin/users`.

Inactive users cannot log in; existing sessions lose access on the next protected
request. An administrator cannot delete/deactivate their own account or remove
their own admin role. At least one active admin must remain. Accounts referenced
by future module records must be deactivated instead of hard-deleted; module
foreign keys must restrict deletion.

Future routes can use `['auth', 'active', 'role:producer']`, or a comma-separated
allow-list such as `role:producer,miller`. `access-admin` is a reusable Gate.
Add per-record ownership policies/checks in each future module as well.

- `resources/views/layouts/front.blade.php`: public/consumer pages.
- `resources/views/layouts/admin.blade.php`: admin and actor workspaces.
- Pages use `@extends`, `@section('title', '...')` and `@section('content')`.
- Breeze component layouts adapt profile/auth pages to the shared layouts.
- Reuse `<x-card>`, `<x-alerts>`, `<x-validation-errors>`, input/error/button
  components and the shared navigation. Keep user content escaped with `{{ }}`.

The visual foundation uses warm cream and olive green across public, account and
admin screens. [Design notes](docs/design.md) describe the shared styles,
components and generated homepage image.

## Password reset and mail

Reset link generation and token-based password reset work. By default,
**`MAIL_MAILER=log`** writes the notification and reset URL to
`storage/logs/laravel.log`; no email is delivered. For local testing, request a
reset link and open the URL from your own log. Automated tests verify the full
reset flow, including password replacement with a valid token.

For actual email delivery, configure your own SMTP transport in the local `.env`
using the options in `config/mail.php`, then run `php artisan config:clear`.
Do not commit SMTP passwords or reset URLs. The base does not require SMTP setup
or verified emails to access the dashboard.

## Eloquent architecture

All application persistence now uses Laravel Eloquent, including Breeze's shared
`App\Models\User` and the business models in `app/Models/Production`.
`User::producerProfile()` is `hasOne`; `ProducerProfile::user()` is `belongsTo`;
`ProducerProfile::farms()` is `hasMany`; `Farm::producerProfile()` is `belongsTo`.
Enum, boolean and decimal casts preserve the existing stored values.

Keep one shared User table/model and use Laravel migrations for every schema
change. Future modules should use Eloquent models, relationships and factories;
do not install a second ORM. Repositories expose the current integration
contracts, while services enforce authorization and mutations. Read
[the persistence guide](docs/persistence.md) before starting a module.

### Updating an existing clone

The shared base, Producer/Farm module and Eloquent refactor are available on
**main** and **develop**. With a clean working tree, update your local main:

```powershell
git fetch origin
git switch main
git pull --ff-only origin main
composer install
php artisan optimize:clear
php artisan migrate
npm.cmd ci
npm.cmd run build
php artisan olivetrace:check-database
php artisan test
```

Keep your existing `.env`, application key and database. The Eloquent conversion
preserves existing tables, IDs, foreign keys and data. The Producer/Farm migration
adds its two tables if you have not already installed the module.
Do not run `migrate:fresh` or reseed common users to update. The old Doctrine
environment variables can be removed from your private `.env`; they are unused.
Replace old `App\Entities\Production` imports with `App\Models\Production` and
use snake_case attributes such as `area_ha`, `olive_variety` and `user_id`.

To add the optional producer demo profile and farms after an update, run
`php artisan db:seed --class=ProductionSeeder`. This requires the existing
`producer@test.com` development account and preserves edited module records.
The full `migrate --seed` setup command is for a fresh development installation.

## Tests and checks

```powershell
php artisan test
php vendor/bin/pint --test
npm.cmd run build
composer validate --strict
composer check-platform-reqs
composer audit
npm.cmd audit
php artisan olivetrace:check-database
```

The normal suite uses SQLite in memory and never resets the development MySQL
database. To run the same suite against MySQL, create a **separate** schema using
administrative credentials and grant your local application user access:

```sql
CREATE DATABASE IF NOT EXISTS olivetrace_testing
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

Then run:

```powershell
php scripts/test-mysql.php
```

This reads credentials from your local `.env` and uses its database name with
`_testing` appended. Tests recreate only that separate testing schema. The test
bootstrap rejects a non-testing MySQL database; do not point tests at real data.
All model tests now use the same Laravel connection, including SQLite in memory.
The GitHub workflow repeats build, formatting, SQLite/MySQL tests and the database
check on pushes/PRs for main and develop.

## Git workflow

`main` is the stable branch; `develop` is integration. Sofiene's current branch is
`feature/sofiene-production`. Team feature branch names are:

```text
feature/sofiene-production
feature/mariem-harvest-mill
feature/oussema-lab-certification
feature/hana-distribution
feature/aymen-consumer-feedback
```

Start from develop, for example:

```powershell
git fetch origin
git switch develop
git pull --ff-only origin develop
git switch -c feature/sofiene-production
```

Use your own feature branch name. Workflow: **feature branch -> Pull Request ->
develop -> integration tests -> Pull Request -> main**. Small, meaningful commits
are expected. Setup/auth/users/layouts are shared and should not be rebuilt per
module. Invite teammates through GitHub Settings > Collaborators.

The final specification supplied by Sofiene supersedes the earlier ownership
swap: Sofiene owns Producer/Farm and Mariem owns Harvest/Mill/Oil Lot.
[Team workflow notes](docs/team-workflow.md) follow that corrected assignment.

## Repository hygiene

`.env`, other local environment files, `vendor`, `node_modules`, builds, logs,
local databases, private keys and temporary/local setup files are ignored.
`.env.example` contains placeholders only. Never commit credentials, API keys,
private configuration or generated password-reset links. Review `git status` and
`git diff --cached` before every commit.

The functional reference is `OliveTrace_Final_Team_Specification.pdf`. The five
modules are integrated on top of the shared infrastructure. Each teammate keeps
their own local database and private AI configuration; Git does not copy these.
