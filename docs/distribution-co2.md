# Distribution CO₂ estimates

Shipment estimates are calculated deterministically when a shipment is saved. Users
cannot set `co2_estimate`; the model overwrites it from shipment distance, bottle
quantity, the product's bottle volume, and transport type.

```text
transported_tonnes =
  (quantity_bottles * (bottle_volume_ml / 1000) * 0.916
   + quantity_bottles * 0.5) / 1000
co2_kg = distance_km * transported_tonnes * emission_factor[transport_type]
```

The oil density assumption is 0.916 kg/L and packaging mass is estimated at 0.5 kg
per bottle. Factors in `config/olivetrace-distribution.php` are kg CO₂ per tonne-km:
truck 0.10, van 0.25, rail 0.03, ship 0.015, and air 0.60. Results are rounded to
two decimal places. Existing shipment estimates are backfilled by the quantity
migration; old rows without a known product or factor retain their existing value.

These are indicative estimates, not measured emissions or a lifecycle assessment.
They do not include warehousing, empty returns, route-specific fuel efficiency,
packaging variation, or production emissions. Cancelled shipments retain their
estimate for history but are excluded from product totals.

## Impact assistant and updating your checkout

The shipment page offers impact advice with a rule-based fallback. To enable
external AI advice, set `OLIVETRACE_IMPACT_AI_ENABLED=true` in your private `.env`.
It uses `FARM_AI_PROVIDER`, `FARM_AI_MODEL` and the matching provider key.
Gemini uses `GEMINI_API_KEY`; the lab continues to select its own `LAB_AI_MODEL`.
Missing credentials or provider failures keep rule-based advice available.

Gemini shipment advice retries temporary connection failures and HTTP 408, 429,
500, 502, 503 and 504 responses, with waits of one and then two seconds. There
are at most three attempts sharing the existing 30-second request budget. Other
errors (including invalid credentials or malformed responses) are not retried.
If Gemini remains unavailable, the result is clearly labelled as rule-based advice;
it is never presented as a successful AI response.

For a demonstration, sign in as `distributor@test.com`, open **Shipments**, select
a shipment, and click **Analyze impact**. A successful Gemini response is labelled
**AI suggestion, advisory only**. On another PC, keep your private `GEMINI_API_KEY`,
set `OLIVETRACE_IMPACT_AI_ENABLED=true`, and run `php artisan config:clear` after
updating `.env`. New checkouts have the flag enabled in `.env.example`.

After pulling the unified main, run `composer install`, `php artisan optimize:clear`,
`php artisan migrate`, `npm.cmd ci` and `npm.cmd run build`. Keep your own `.env`
and existing database; no reseeding is needed. The new migration adds shipment
quantity and recalculates existing shipment estimates with a default quantity of one.
