# Project verification

Verified on 7 October 2026 on Sofiene's Windows computer after the Eloquent
refactor on `feature/sofiene-production`. These results cover the shared base
and Sofiene's Producer/Farm module. Integration follows the pull-request workflow
from the feature branch to develop, then from develop to main.

## Environment

- PHP 8.4.26 with Laravel/MySQL extensions and Composer 2.10.3.
- Laravel 12.69.3 and Breeze 2.4.2 with Blade.
- Eloquent for User, ProducerProfile and Farm persistence.
- MySQL Community 8.4.9 on 127.0.0.1:3306.
- Git 2.46.1, Node 24.20.0 and npm 11.6.1.

## Checks completed

| Check | Result |
| --- | --- |
| Locked Composer install with package discovery | Passed |
| Strict manifest validation and platform requirements | Passed |
| Dependency removal | 14 unused packages removed; no remaining packages upgraded |
| Composer security audit during dependency resolution | No reported advisories |
| Frontend production build | Passed |
| SQLite suite | 61 passed, 395 assertions |
| Separate MySQL testing suite | 61 passed, 395 assertions |
| Pint formatting check | Passed |
| Route cache and Blade view cache | Passed; caches cleared afterward |
| Database connection and required tables check | Passed |
| Development migration command | Nothing to migrate; schema preserved |
| ProductionSeeder rerun | Passed without changing existing records |
| Existing user/profile/farm data fingerprints | Unchanged from the pre-refactor snapshot |
| Git diff whitespace check | Passed |

A private MySQL backup was saved in the ignored local working directory before
the conversion. No application migration was edited or added. All primary keys,
foreign keys, stored values and timestamps were kept. Tests only recreate an
isolated testing schema, never the development database.

## Behavior covered

The suite exercises shared registration, login/logout, password reset/profile,
all roles, inactive sessions, admin-only user management and final-admin
safeguards. Producer/Farm checks cover full CRUD, ownership and filter isolation,
restricted foreign-key deletion, archival, admin moderation, disabled-state
protection, private logo replacement/removal and safe public origin display.
The development seeder preserves edited producer and farm records.

Persistence checks read rows inserted with the original column names through
Eloquent, verify relations and enum/decimal/timestamp casts, and prove that a
single transaction rolls back User, ProducerProfile and Farm together. Generic
test-only references verify the replacement foreign-key exception handling.
The test harness no longer needs two ORM connections or temporary SQLite files.

AI adapter tests use mocked OpenAI and Ollama responses. They cover limited
inputs, escaped/validated output, throttling, missing configuration and failures
without changing farm records. Live AI remains unconfigured and unverified.

## Browser checks

Home, Breeze login/logout, producer dashboard/profile, farm lists/details,
populated edit form, public origin card, and admin producer/farm lists rendered
successfully at `http://127.0.0.1:8000`. No application console warnings or
errors were reported by the browser log check.

Password reset still defaults to the local log mailer; automated tests verify
the token reset flow. Actual SMTP delivery is not configured.

## Team integration

The GitHub workflow now uses `olivetrace:check-database` alongside Composer,
build, formatting and SQLite/MySQL tests. These are local verification results;
remote checks run when the workflow is triggered on main/develop or a pull
request. See the README and `docs/persistence.md` for the exact update steps.
