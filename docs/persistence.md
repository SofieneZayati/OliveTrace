# Eloquent persistence

The application uses one ORM: Laravel Eloquent. On 7 October 2026, Sofiene
requested replacement of the previous Doctrine integration. Breeze authentication
continues to use `App\Models\User`; all business models use Eloquent too.

## Existing schema and data

The conversion changes PHP persistence code only. The existing migrations,
tables, primary keys, restricted foreign keys, stored enum strings, decimal
precision and timestamps remain unchanged. Run `composer install`,
`php artisan optimize:clear` and `php artisan migrate` after pulling. Keep your
local `.env` and application key. Do not reset the database for this change.

The Laravel Doctrine package, entity manager, mapping configuration, proxy
generation and Doctrine health command have been removed. `doctrine/inflector`
may still appear in Composer's dependency tree because Laravel uses it for
English word inflection; it is not an ORM or another persistence layer.

## Models and relationships

| Model | Table | Relationships |
| --- | --- | --- |
| `App\Models\User` | `users` | `producerProfile(): HasOne` |
| `App\Models\Production\ProducerProfile` | `producer_profiles` | `user(): BelongsTo`, `farms(): HasMany` |
| `App\Models\Production\Farm` | `farms` | `producerProfile(): BelongsTo` |

Use snake_case column attributes (`user_id`, `display_name`, `area_ha`, etc.)
and the relationship methods above. No duplicate User model or user table is
needed. Farm casts return the existing farming/irrigation/status enums, decimal
strings and publication booleans. Eloquent manages `created_at` / `updated_at`.

Ownership IDs, moderation status, enabled flags and logo paths are excluded
from public mass assignment. Controllers accept validated Form Requests;
services assign privileged fields explicitly after authorization. Models hide
private fields from generic serialization, and `PublicOrigin::forFarm()` remains
the allow-list for public display. Hidden fields alone are not authorization.

## Working on future modules

1. Add Eloquent models under `app/Models/<Module>` and Laravel migrations under
   `database/migrations`. Add `belongsTo` / `hasMany` relationships on both sides
   when the team agrees on the integration contract.
2. Use `foreignId()->constrained()->restrictOnDelete()` for references that
   preserve history. Keep the existing unsigned BIGINT IDs.
3. Add Laravel Form Requests, ownership policies and active/role middleware.
   Pass validated data to a service, with `DB::transaction()` for changes that
   must succeed together. All models now share the same database connection.
4. Use standard `HasFactory` and Laravel factories. For example:

   ```php
   $profile = ProducerProfile::factory()->for($producer)->create();
   $farm = Farm::factory()->for($profile, 'producerProfile')->create();
   ```

5. Use `Farms::selectableForUser($producerId)` for eligible farm selections.
   It still returns an array, now containing Eloquent Farm models. Revalidate
   eligibility and ownership when a future harvest is submitted. Public pages
   should render the safe array returned by `PublicOrigin::forFarm($farmId)`.
6. Use `RefreshDatabase` in tests. SQLite in memory and the isolated MySQL
   `_testing` schema both exercise the migrations, factories and relationships.
   Never run tests against the development database.

## Checks

```powershell
php artisan olivetrace:check-database
php artisan test
php scripts/test-mysql.php
```

The database command checks connectivity and required tables without changing
data. Schema changes still use `php artisan migrate`; there is no second schema
management tool.

References: [Laravel 12 relationships](https://laravel.com/docs/12.x/eloquent-relationships),
[casts](https://laravel.com/docs/12.x/eloquent-mutators) and
[factories](https://laravel.com/docs/12.x/eloquent-factories).
