# OliveTrace

OliveTrace is a university web project for tracing Tunisian olive oil from farm
to consumer. The team is Sofiene, Mariem, Oussema, Hana and Aymen.

**This branch contains the shared base and Sofiene's Producer & Farm Management module.**
Authentication, roles, common user management, layouts and infrastructure are
shared work; they do not count as an individual student's module.

## Included

- Laravel 12, MySQL and a configured Doctrine entity manager.
- Breeze with Blade: register, login, logout, password reset and account profile.
- Six roles, active/inactive accounts, protected routes and a reusable role middleware.
- Admin user list, search, role/status filters, details, edit, role change,
  activation/deactivation and deletion of unlinked accounts.
- Front Office and Back Office layouts, shared navigation, alerts, validation
  errors, buttons, cards and a simple dashboard.
- Development accounts, automated tests and a GitHub Actions check workflow.

- Sofiene's Doctrine ProducerProfile / Farm relationship, ownership-protected CRUD,
  validated forms, private logo uploads, admin correction/moderation, demo factories
  and seeders, a public origin card and an advisory sustainability AI integration.

Harvest/Mill/Oil Lot, Laboratory/Certification, Product/Distribution and Consumer
Traceability/Feedback remain the other members' future modules. See
[Sofiene's implementation and integration contracts](docs/sofiene-production.md).
Real AI suggestions require a configured OpenAI key or an installed local Ollama
model; normal CRUD works without either. Credentials stay in your local `.env`.

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

Laravel 12 itself supports PHP 8.2, but the chosen Doctrine integration needs
PHP 8.3. The project's PHP requirement is therefore **8.3**. The lock file targets
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
php artisan olivetrace:check-doctrine
php artisan serve
```

Open **http://localhost:8000**. The Doctrine check must succeed; this base reports
two business entities on this branch. Keep the server terminal open while using the app.

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

## Doctrine architecture

`laravel-doctrine/orm` **3.3.3** is installed and supports Laravel 12 / PHP 8.3.
Doctrine ORM **3.7.4** and DBAL **4.5.0** connect to the same MySQL database.

The shared `App\Models\User` and Breeze authentication intentionally use Eloquent.
**All future business persistence must use Doctrine entities, repositories and
associations.** Production mappings scan only `app/Entities`, including
Sofiene's ProducerProfile and Farm. Place repositories under `app/Repositories`.

Use Laravel migrations for schema changes so the team has one setup command.
Reference shared users with scalar user IDs plus restricted MySQL foreign keys;
do not create duplicate user models/tables. Business-to-business relationships
use Doctrine associations. Read [the Doctrine architecture guide](docs/doctrine.md)
before beginning a module, especially its validation and schema-tool boundaries.

## Tests and checks

```powershell
php artisan test
php vendor/bin/pint --test
npm.cmd run build
composer validate --strict
composer check-platform-reqs
composer audit
npm.cmd audit
php artisan olivetrace:check-doctrine
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
The Doctrine persistence fixture is test-only and leaves no business table in
the application schema. The GitHub workflow repeats build, format, SQLite/MySQL
suite and Doctrine checks on pushes/PRs for main and develop.

## Git workflow

`main` is the stable branch; `develop` is integration. Sofiene's current branch is
`feature/sofiene-production`; the other module branches remain planned:

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

The functional reference is `OliveTrace_Final_Team_Specification.pdf`. This base
implements shared Phase 0 infrastructure plus Sofiene's Phase 1 origin module and
AI adapter. The remaining modules and live AI configuration are the next stages.
