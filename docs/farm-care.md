# Sofiene's farm care workflow

This addition belongs to `feature/sofiene-production`. ProducerProfile and Farm
remain Sofiene's original related entities. FarmActivity and FarmAdviceReport
add an advanced farm-care workflow; authentication and user management remain
shared work. Harvests, milling, laboratories, distribution and consumer features
keep their existing contracts.

## Update an existing clone

```powershell
git fetch origin
git switch feature/sofiene-production
git pull --ff-only origin feature/sofiene-production
composer install
npm.cmd ci
php artisan migrate
php artisan optimize:clear
npm.cmd run build
php artisan serve
```

Keep the existing private `.env`, app key and local MySQL credentials. The new
AI workflow uses the existing `FARM_AI_PROVIDER`, `FARM_AI_MODEL` and
`GEMINI_API_KEY` settings; no additional AI subscription or key is needed.
The map uses Leaflet from the npm lock file, installed by `npm.cmd ci`.
Environment URLs must be plain URLs, not Markdown links. Normal farm care and
saved reports work without making an AI request.

For optional development journal entries, after the ordinary development seed:

```powershell
php artisan db:seed --class=FarmJournalDemoSeeder
```

This seeder adds two clearly named demo plans to Farm El Baraka for
`producer@test.com`. Rerunning it preserves existing entries and edits. It is
not part of the default shared seeding and never inserts simulated AI reports.

For a larger optional dataset covering maps, irrigation totals and journal states:

```powershell
php artisan db:seed --class=FarmCareDemoSeeder
```

This inserts four private `[Demo]` farms under `producer@test.com`, with
illustrative regional coordinates in Sfax, Zaghouan, Beja and Kairouan. Two are
active, one archived and one disabled. They contain 16 clearly labelled fictional
activities: completed, upcoming, overdue and cancelled. The Sfax farm includes a
completed declaration of 24.50 m³ irrigation water. Archived/disabled farms help
demonstrate read-only history and filtering. They are not real farms or measured
environmental outcomes.

The seeder inserts missing records by demo farm name and activity title. It
preserves existing records and edits; renaming a demo farm/title allows a fresh
copy under its original name on the next run. It does not reset user accounts,
change other module records, or fabricate AI reports. Generate a genuine saved
action plan from an active demo farm to test the AI workflow. This optional
seeder refuses production and is separate from the shared default seed.

## Professor demonstration

Use a local development installation and `producer@test.com` / `OliveTrace123!`.
These are public development credentials, not production accounts.

1. Open **My farms**, then a farm. Open **Activity journal**.
2. Plan an inspection for yesterday. It appears in Planned and Overdue.
3. Edit the plan. Complete it today; Planned/Overdue decrease and Completed
   increases. The completed entry becomes immutable history.
4. Plan an irrigation activity. On completion, an actual positive water quantity
   in m³ is required. The monthly total sums completed declarations only.
5. Plan and cancel another task. It remains visible under Cancelled and cannot
   be reopened or included as completed irrigation.
6. Back on the farm, click **Generate saved action plan**. This makes a real
   provider request and redirects to a saved report. Read its facts, record
   window, data gaps, follow-up priorities, reasons and referenced activity IDs.
7. Expand **Review and add to my plan**, choose the title/date, and check the
   review box. No activity is added without this explicit step.
8. Complete the accepted activity in the journal. Reopen its linked report:
   the original advice/input snapshot is unchanged, but its linked task shows
   Completed. Repeating adoption never creates another copy.
9. Use **Print / save PDF** for the browser's print dialog if a report copy is
   useful. This exports the private advisory report, not a certificate.
10. As admin, inspect a farm's journal/reports. Admin cannot accept work on the
    producer's behalf. Disable the farm: its journal and reports stay readable,
    while generation and new activity changes are blocked.
11. Show that harvest selection and the public origin card still work for active
    farms. Public output contains no journal, reports, private notes or GPS.

The advanced feature is a stateful planning/completion workflow with validated
quantities and dates. The AI feature is contextual decision support followed by
human review and tracked work. Recorded completion is not proof of sustainability
improvement; declared farming practices do not establish certification.

## Private farm maps

1. Open **My farms**, then **Explore farms on the private map**. Admins have
   the equivalent link from **Farms**. Filter by name/producer, governorate or
   status. Producers see only their own farms; admins can inspect all farms.
2. Open a farm and choose **Edit farm**. In **Optional private coordinates**,
   click **Load private map**. Click a point or drag the marker to update both
   fields. Manual latitude/longitude entry stays available.
3. Use **Center on coordinates** to bring a typed location into view, or
   **Show Tunisia** to return to the default country view. Recentring does not
   assign a farm location. **Clear coordinates** empties both fields.
4. Click **Save farm** to persist changes. Map interactions alone do not save.
5. Farm details include a private map; the overview shows saved markers with
   links to farm details. Farms without coordinates receive no invented pin.

Leaflet 1.9.4 is bundled locally through Vite and loaded on demand. OpenStreetMap
tiles load only after the user presses the map button. No new API key, browser
location permission, address search or geocoding service is required. Tile access
needs an Internet connection; coordinate inputs and normal farm forms still work
when JavaScript or the tile service is unavailable.

Loading a map sends browser requests to OpenStreetMap, including the viewer's IP
and viewed tile area. The UI explains this before loading. Farm names, notes and
coordinates are not posted to a geocoding API. Exact GPS stays restricted in
OliveTrace; it is excluded from public origin/consumer output and Gemini input.
The overview uses authenticated role middleware and an owner-scoped query. It
returns at most 500 locations, with a notice to narrow filters if more match.

Markers are producer-declared points, not cadastral boundaries, verified areas
or proof of ownership/certification. Existing paired/ranged server validation
continues to apply, including clearing both fields and valid zero coordinates.
No migration, new farm column or change to another member's module is required.

Map configuration is in `config/farm-map.php`; the reusable Blade component is
`resources/views/components/farm-map.blade.php`; picker, markers and safe text
popups are in `resources/js/farm-map.js`. `FarmMapController` supplies only
authorized map data through `Farms::mapData()`. The same filter query still
powers the existing paginated farm list. Provider URL/attribution can be changed
in the configuration together if hosting requirements change.

Attribution stays visible. Browser HTTP caching is respected; there is no
prefetch, offline download, tile proxy or automated background map fetching.
See [Leaflet's stable release](https://leafletjs.com/download.html) and the
[OpenStreetMap tile usage policy](https://operations.osmfoundation.org/policies/tiles/).

Browser checks on 19 October 2026 used a clearly labelled temporary farm with an
illustrative public Sfax location. Selecting, dragging, clearing, typing, saving,
detail maps and overview marker links were checked. The test farm was removed;
the original farm/profile/user records were preserved.

## Server validation and code paths

| Responsibility | File |
| --- | --- |
| Plan title/type/date/notes, prohibited ownership and status injection | `app/Http/Requests/Production/FarmActivityRequest.php` |
| Ownership, active farm/profile, planned-only edits, completion/cancellation and conditional water/date rules | `app/Services/Production/FarmJournal.php` |
| Activity filtering, journal counters and scoped nested record lookup | `app/Http/Controllers/Production/FarmActivityController.php` |
| Selection of at most 30 structured records and deterministic selected-record totals | `app/Services/Production/FarmAdviceContext.php` |
| Provider request, structured JSON, enum/text/evidence validation and safe failure | `app/Services/Production/FarmSustainabilityAssistant.php` |
| Save snapshot, review checkbox/date validation, transactional duplicate prevention | `app/Services/Production/FarmActionPlans.php` |
| Report routes and owner/admin authorization | `app/Http/Controllers/Production/FarmAdviceReportController.php` and `routes/production.php` |
| Eloquent relationships and enums | `app/Models/Production/FarmActivity.php`, `FarmAdviceReport.php`, `Farm.php`; `app/Enums/FarmActivityType.php`, `FarmActivityStatus.php` |
| Journal and report Blade pages | `resources/views/production/activities/` and `resources/views/production/reports/` |

Validation examples: reject an unknown type, overlong title, malformed date, plan
more than a year away, ownership injection, completion before its planned date
or after today, missing/zero/negative irrigation water, and water supplied for a
non-irrigation activity. A recommendation date must be today or later. Planned
manual entries may be retrospective (from 2000 onward). Checks run on the server,
even when browser HTML validation is bypassed.

Management forms use `novalidate` so invalid submissions reach Laravel and show
the server-rendered error summary rather than a browser validation popup. Form
Requests and services remain authoritative; HTML attributes are only metadata.
See [the module update checklist](sofiene-module-checks.md) for teammate setup,
missing-table diagnostics and a short server-validation demonstration.

Completed/cancelled entries cannot be edited, reopened or deleted through the
application. Cancel a mistaken plan instead of removing its history. Activities
and reports have restricted foreign keys: deleting a farm with this history
archives it, preserving the origin and all downstream references.

## AI facts and limits

The provider receives region/delegation, area, variety, farming/irrigation methods,
presence indicators for notes/GPS, and a journal snapshot. The snapshot selects
planned dates from 90 days ago through 30 days ahead, newest first, limited to 30
entries. Both the matching and included counts are shown; summaries cover only
included entries. Activity fields sent are ID, type, status, planned/completion
dates and recorded irrigation water. Names, contact information, exact GPS,
activity titles, free-text notes and logos are excluded.

Reports store provider/model, input facts and validated advice in MySQL. They
are immutable through application routes and private to the producer/admin.
There is no automatic AI request on page load, no automatic scheduling and no
fake AI fallback. Unsupported/malformed responses, quota limits and outages
produce a readable error without saving a report or modifying farm work.
Old reports remain available during provider outages.

The model supplies 1–4 recommendations with a supported activity type, a
high/medium/low follow-up priority, reason and optional supporting activity IDs.
Every cited ID must be in the supplied snapshot. Reasons remain AI-generated
advice requiring human review; schema checks do not prove agronomic correctness.
The application computes record counts and declared-water sums itself; they are
not model-generated sustainability scores. Missing records are not proof of
missing work. No soil readings, disease diagnoses, weather or chemical doses are
invented by the application.

## Integration and verification

Existing farm/profile IDs, statuses and public fields are unchanged.
`Farms::selectableForUser()` and `PublicOrigin::forFarm()` retain their interfaces.
No harvest, mill, lab, certificate, product, shipment, feedback or complaint
records are written by this workflow. The two new migrations create production
tables and optional activity-to-report links only.

```powershell
php artisan test
php scripts/test-mysql.php
php vendor/bin/pint --test
npm.cmd run build
php artisan olivetrace:check-database
```

`FarmJournalTest` and `FarmActionPlanTest` cover conditional validation,
authorization, nested-record isolation, immutable states/snapshots, bounded
private AI inputs, provider failures, duplicate adoption, admin inspection,
archival and compatibility with public origin/harvest selection. Provider tests
mock HTTP; live Gemini report generation and reviewed adoption were checked in
the local browser on 18 October 2026.

`FarmMapTest` covers private map scoping, roles, filters, escaped farm names,
public-coordinate exclusion, paired/ranged validation, valid zero coordinates
and clearing saved coordinates. Interactive rendering and field synchronization
are also checked in the local browser.
