# Sofiene: Producer & Farm Management

The final specification supplied by Sofiene supersedes the older ownership swap
in the shared-base notes. Sofiene owns `producer_profiles` and `farms`; Mariem
owns the future Harvest/Mill/Oil Lot module. Branch: `feature/sofiene-production`.

## Assignment evidence

| Requirement | Implementation |
| --- | --- |
| Two related domain entities | Eloquent `ProducerProfile` / `Farm`, `hasMany` / `belongsTo`, real MySQL foreign keys. |
| CRUD excluding User | Full farm CRUD. Producer profile create/read/update/delete only when it has no farms or linked records. Historical farms are archived. |
| Advanced forms | Form Requests, required fields, controlled cultivation values, positive area, paired/ranged GPS coordinates, private image uploads, inline errors and preserved input. |
| Authorization | Role middleware, ownership policy and service checks; owner cannot reassign profile IDs or bypass admin-disabled status. |
| Blade Front / Back Office | Management pages use `layouts.admin`; public origin uses `layouts.front`; inherited templates, field components and reusable `x-origin-card`. |
| Factories and seeders | Laravel Eloquent factories and idempotent `ProductionSeeder`, with two Sfax/Chemlali demo farms. |
| AI advanced feature | Sustainability assistant with OpenAI Responses / local Ollama adapters, validated structured output and graceful outages; advisory only. |
| Integration | Stable `farm_id`, `Farms::selectableForUser()` for Mariem, `PublicOrigin::forFarm()` and origin card for Aymen. |
| Git | Dedicated feature branch and meaningful local commits; review/integration into `develop` remains the team workflow. |

On 7 October 2026, Sofiene requested a refactor to Eloquent for the whole
application. Authentication and business models now use that single ORM. The
university documents contain conflicting ORM wording; this records the chosen
project architecture and does not claim instructor confirmation of the rubric.

## Setup and individual demo

After activating a PHP 8.3+ terminal, install the locked dependencies and run:

```powershell
composer install
npm.cmd ci
php artisan migrate
php artisan db:seed --class=ProductionSeeder
npm.cmd run build
php artisan serve
```

`ProductionSeeder` expects `producer@test.com` from `DevelopmentUserSeeder`.
For a fresh development database, `php artisan migrate --seed` creates everything.
For an existing database, do not routinely reseed common users or regenerate the
app key. The module seeder adds missing records without overwriting farm/profile
edits and refuses production environments.

Sign in as `producer@test.com` / `OliveTrace123!`. Open **Producer profile**, then
**My farms**. Farm El Baraka (12.50 ha) and Parcel En Nour (6.25 ha) are in Agareb,
Sfax and grow Chemlali olives. Demonstrate invalid form submission, correcting
it, farm creation/update, private image upload, public origin and archival.
An unused profile can be deleted without deleting the common login account.

As `admin@test.com` with the same demo password, inspect **Producers** and
**Farms**, correct details or disable invalid records. Admin moderation preserves
records; it does not hard-delete another producer's profile or farms.

## Contract for Mariem

- `farms.id` is unsigned BIGINT. Add a matching `harvests.farm_id` foreign key
  with **RESTRICT** deletion and an Eloquent `belongsTo(Farm::class)`
  relationship on the future Harvest model. Do not copy origin fields into Harvest.
- `Farm::producerProfile()` is the relationship; `ProducerProfile::user()`
  links `user_id` to the common User model. No second User model is created.
- Inject `App\Repositories\Production\Farms` and call
  `selectableForUser($authenticatedProducerId)`. It returns active farms under
  enabled profiles. Validate the current account's producer role/active status,
  and re-check farm ownership/status on harvest submission. Public visibility is
  not required for a harvest.
- Selector labels can read `name`, `governorate`, `delegation`, `olive_variety`
  and `area_ha`; retain the exact farm ID.
- Existing history remains readable after archival/disablement. These states
  prevent future selection. Farm deletion archives when `harvests.farm_id` history
  exists. Restricted foreign keys also guard other references and concurrent
  inserts; when deletion is blocked, use Archive.
- Statuses: `active`, `archived`, `disabled`. Farming: `conventional`, `organic`,
  `integrated`. Irrigation: `rainfed`, `drip`, `sprinkler`, `surface`.
- Add an inverse Harvest `hasMany` relationship only as an agreed integration change. This
  branch does not implement Mariem's entities or screens.

## Contract for Aymen / public origin

Inject `App\Services\Production\PublicOrigin`, call `forFarm($farmId)`, and render
a non-null result with `<x-origin-card :origin="$origin" />`. A null result means
publication is unavailable; unknown farm IDs produce 404. The standalone origin
demo route is `/origins/farms/{farm}`.

Both producer profile and farm must opt in. The profile must be enabled, the
farm active and its user an active producer. Cards exclude phones, addresses,
login emails, free-text notes, exact GPS and logos. Logos are private owner/admin
resources accepting JPEG/PNG/WebP only. Declared organic farming is never shown
as certification. Oussema owns certificate evidence; Aymen owns the full trace page.

## Configure and demonstrate real AI

CRUD works without AI, but the final individual AI demo requires a configured
service. Adapters and mocked-provider tests are included. Put credentials only
in the ignored local `.env`, never in Git or chat:

```dotenv
FARM_AI_PROVIDER=gemini
FARM_AI_MODEL=gemini-3.1-flash-lite
GEMINI_API_KEY=your_private_key
```

OpenAI remains supported with `FARM_AI_PROVIDER=openai`,
`FARM_AI_MODEL=gpt-4.1-mini` and `OPENAI_API_KEY`.
Alternatively, with Ollama already running and a model installed:

```dotenv
FARM_AI_PROVIDER=ollama
FARM_AI_MODEL=your_installed_model_name
OLLAMA_URL=http://127.0.0.1:11434
```

Run `php artisan config:clear`, open a farm and click **Generate suggestions**.
There is no automatic AI request. Inputs are region/delegation, area, variety,
farming/irrigation practice and boolean indicators of missing notes/coordinates.
No personal/contact fields, precise coordinates or free-text notes are sent.
OpenAI requests set `store=false`. Output is escaped, validated and ephemeral;
it never edits records or certifies sustainability/organic status. The producer
must review advice, with local agronomic guidance. Missing configuration,
refusals, invalid output and service outages leave normal CRUD available.
No rule-based fallback is represented as AI.

Adapter references: [Gemini structured outputs](https://ai.google.dev/gemini-api/docs/structured-output),
[OpenAI structured outputs](https://developers.openai.com/api/docs/guides/structured-outputs)
and [Ollama chat API](https://docs.ollama.com/api/chat).

## Checks and remaining team work

```powershell
php artisan test
php scripts/test-mysql.php
php vendor/bin/pint --test
npm.cmd run build
php artisan olivetrace:check-database
```

Module tests use `RefreshDatabase` with SQLite in memory or the separate MySQL
testing schema. Users, profiles and farms share one Laravel connection and
transaction boundary. Tests never touch development farm data. Integration tests
now use the real harvest, milling, certification and distribution tables.

Live Gemini generation was verified on Sofiene's machine on 8 October 2026.
Every teammate needs their own private configuration to reproduce it. The five
modules are now integrated, including the consumer trace page's public-origin checks.
