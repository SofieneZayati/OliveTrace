Eloquent query repositories go here. Keep persistence and ownership checks out
of Blade views. Authorize mutations through policies and services; use Laravel
transactions when several model changes must succeed together.

`Production/Farms.php` preserves the farm selector contract for Mariem.
`Production/ProducerProfiles.php` queries Eloquent profiles.
See `docs/persistence.md` for models, relationships and team conventions.
