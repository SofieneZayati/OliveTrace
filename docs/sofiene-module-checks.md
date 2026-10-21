# Updating and checking Sofiene's module

`feature/sofiene-production` includes the team integration from
`feature/oussema` at `b131d0f`, including Oussema's and Hana's additions.
ProducerProfile, Farm, FarmActivity and FarmAdviceReport remain Eloquent models.

## After pulling

Keep your existing private `.env` and app key. Start your MySQL service, then run
these commands from a PHP 8.3+ terminal in the project folder:

```powershell
composer install
npm.cmd ci
php artisan migrate
php artisan optimize:clear
php artisan olivetrace:check-database
npm.cmd run build
php artisan serve
```

Finish `composer install` before starting other Artisan commands. The integrated
certificate PDF feature adds a Composer dependency; copying PHP source without
installing the locked dependencies is not a complete update.

The database check now verifies the journal and saved-report tables as well as
users, producer profiles and farms. If it reports a missing table, run migrations
against the same database configured in your private `.env`. Do not use
`migrate:fresh` or reset existing accounts to fix an update.

If existing demo accounts are already present, do not run the shared user seed
again. The optional `FarmCareDemoSeeder` can add the clearly named map/journal
fixtures described in [farm-care.md](farm-care.md).

Maps require the current Vite build. Leaflet is installed through `npm.cmd ci`;
OpenStreetMap tiles load after pressing **Load private map** and require Internet.
No map API key is needed. Farms without saved coordinates intentionally have no
marker. Their coordinates can be entered manually or using the picker.

New AI reports require the private `GEMINI_API_KEY`, `FARM_AI_PROVIDER=gemini`
and an available `FARM_AI_MODEL`. Use the placeholders in `.env.example`; never
copy a real key into Git. Re-run `php artisan config:clear` after changing `.env`.
Saved reports and manual farm care remain available without a new AI request.

## Server-side validation for the professor

Producer/profile, farm, activity, activity-completion, recommendation-adoption
and farm-moderation forms use `novalidate`. Invalid submissions reach Laravel
instead of stopping at the browser's native validation popup. Input types and
accessible required/range attributes remain useful form metadata.

The authoritative checks are on the server:

- `app/Http/Requests/Production/ProducerProfileRequest.php`: profile fields,
  phone, private image type/size/dimensions and protected ownership fields.
- `app/Http/Requests/Production/FarmRequest.php`: positive area, allowed
  governorate/enums, paired/ranged GPS, text limits and protected fields.
- `app/Http/Requests/Production/FarmActivityRequest.php`: plan title/type/date,
  notes and prohibited ownership/status/report injection.
- `app/Services/Production/FarmJournal.php`: active farm/profile and ownership,
  planned-only changes, completion dates and conditional positive water.
- `app/Services/Production/FarmActionPlans.php`: explicit review, valid adoption
  date, scope and duplicate prevention.
- `app/Policies/ProductionPolicy.php` and route middleware: roles, active
  accounts and record ownership.

Laravel redirects back with an error summary, field errors where applicable and
old form input. Invalid updates leave saved records unchanged. Journal filters
keep the actual query selection after a failed status change; the attempted
`completed` value must not silently replace the selected filter.

## Short browser check

Use the development-only `producer@test.com` / `OliveTrace123!` account.

1. Open the producer profile, then a farm. Test an invalid area such as `0`;
   observe Laravel's error summary and preserved input. Correct it and save.
2. Edit a farm's location, load the map and click/drag a point. Save and reopen.
   Check both coordinate fields and the private farm overview.
3. On an active farm, plan an irrigation task for yesterday. Check Overdue.
   Complete it today: `0` water is rejected; `5.00` m3 is accepted and added to
   the current month's completed-irrigation declaration total.
4. Generate a saved action plan. Check the facts and structured activity
   snapshot. An actual provider response is required; seeders do not fake AI.
5. Try adopting an action without reviewing it: Laravel rejects the submission.
   Review it, choose today, and add it. Complete the linked task and reopen the
   report; the advice snapshot stays fixed while the linked status is current.
6. As admin, inspect journals/reports and temporarily disable a disposable farm.
   History remains readable but new work/generation/adoption are blocked.
   Consumers and other producers cannot access the owner's private records.

Automated checks include full profile/farm CRUD, image upload/replacement,
publication/privacy, permissions, map data/coordinates, journal transitions,
archival, AI response validation, reviewed adoption and provider failures.
`ProductionServerValidationTest` additionally sends invalid HTTP requests
directly, bypassing all browser checks, and verifies that saved data is unchanged.

```powershell
php artisan test
php scripts/test-mysql.php
php vendor/bin/pint --test
npm.cmd run build
```

Tests must use the isolated SQLite/MySQL test databases enforced by
`tests/TestCase.php`, never the development database. See
[farm-care.md](farm-care.md) for the complete original/new feature demonstration.

## Verification on 21 October 2026

The integrated source passed 200 tests / 1,425 assertions on SQLite and the
separate MySQL testing database, plus Pint, Composer validation/platform checks
and the Vite build. Browser checks covered producer/admin login, farm
create/update, Laravel profile/area/water validation, the location picker and
overview, overdue work, completed irrigation totals, and consumer map denial.
A real Gemini report was generated from a temporary farm's recorded irrigation;
its reviewed recommendation was adopted, completed and displayed as completed
from the original saved report. Admin inspection and disabled-farm generation
controls were also checked. Existing user/profile/farm records were preserved.
