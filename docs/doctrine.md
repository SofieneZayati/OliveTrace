# Doctrine and Breeze

## Verified dependency choice

The base uses `laravel-doctrine/orm` **3.3.3**, Doctrine ORM **3.7.4** and
Doctrine DBAL **4.5.0**, as resolved in `composer.lock`. The installed integration's
Composer requirements explicitly include Laravel 12 and PHP `^8.3`.

The older integration 1.x/2.x documentation is not the compatibility reference
for this project. Check the installed package's `composer.json` and the current
[package metadata](https://packagist.org/packages/laravel-doctrine/orm) before
upgrading. The provider and `EntityManagerInterface` binding are auto-discovered.
The lock file is resolved against PHP 8.3.0 so developers with PHP 8.3 or 8.4
receive the same compatible dependency versions.

## Shared account boundary

`App\Models\User` is the sole Eloquent application model. Breeze, Laravel's
session guard, password broker, notifications, profile and common admin user
management all use it. This is an intentional shared-authentication exception;
rewriting Breeze's authentication provider would add avoidable integration work.

**Every future business entity and business-to-business association must use
Doctrine.** Do not generate business Eloquent models or use `belongsTo`/`hasMany`
for module relationships. Authentication and user management are common work,
not one student's module.

Laravel's validation presence verifier remains enabled. The Doctrine package's
replacement is disabled in `config/doctrine.php`, because Breeze uses
`unique:users,email` and an Eloquent user class. Business Form Requests should
perform repository-based existence/ownership checks, or use an explicit
table-based Laravel validation rule; ORM persistence still goes through Doctrine.

## Adding a module later

1. Add PHP attribute entities under `app/Entities`, optionally in module
   subdirectories. Only that directory is scanned by Doctrine.
2. Map business-to-business relationships with Doctrine's `ManyToOne`,
   `OneToMany`, etc. Agree on the owning side, column names and deletion rules
   with the other module owner before merging.
3. Add repositories under `app/Repositories`. Inject
   `Doctrine\ORM\EntityManagerInterface` into repositories/services. Use
   `persist`, `flush`, repositories and DQL for business persistence.
4. Use one **Laravel migration runner** for the entire MySQL schema. Add reviewed
   Laravel migrations in `database/migrations`, matching the Doctrine mapping.
   This uses Laravel's schema tools, not Eloquent for business persistence.
   Everyone runs `php artisan migrate`. No separate Doctrine migrations package
   or second migration ledger is needed for this base.
5. Use Form Requests, middleware, policies and service ownership checks. A role
   alone does not authorize editing someone else's records. Avoid a global admin
   bypass that would silently skip module workflow rules.
6. Add tests, a Doctrine-aware module seeder, and pages extending the shared
   layouts. Future module seeders must use the entity manager rather than
   Eloquent factories for business objects.

## Business ownership linked to shared users

Doctrine cannot associate a mapped entity directly with an Eloquent User.
At this boundary, store a scalar user ID (`user_id`, or the agreed actor-specific
name) in the Doctrine entity. The migration must define a real foreign key to
`users.id` with **restricted deletion**, matching Laravel's unsigned big integer
ID type. Resolve the common account through the shared User model when needed.

Do not create another users table, copy account details into business tables, or
map a second writable user entity. Relationships between all business objects
remain normal Doctrine associations. A read-only Doctrine account adapter can
be considered later if the team needs direct user associations, as a shared,
reviewed change rather than five separate implementations.

Keep traceability history: archive referenced records rather than cascading a
user or upstream record deletion through the chain. The base catches restricted
user deletion and asks the administrator to deactivate the account instead.

## Safe checks

```powershell
php artisan olivetrace:check-doctrine
```

This validates mapping metadata and executes `SELECT 1` through Doctrine. It does
not compare, generate or change schemas. The base reports **0 business entities**.
Doctrine create/update/drop schema commands do not know about all Laravel-owned
tables. Do not run them against the shared application database. Use reviewed
Laravel migrations instead; never apply a generated destructive schema diff.

The test-only `DoctrineProbe` lives under `tests/Fixtures`, outside the production
mapping scope. Its temporary table exists only in the isolated test connection;
the persistence test creates, reads, updates and removes it, then drops it.

References: [Laravel Doctrine source](https://github.com/laravel-doctrine/orm),
[Doctrine attribute mapping](https://www.doctrine-project.org/projects/doctrine-orm/en/3.7/reference/attributes-reference.html).
