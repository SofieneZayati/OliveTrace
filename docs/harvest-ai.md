# Harvest oil quantity estimates

The producer's harvest detail page and admin harvest inspection page offer
**Estimate oil quantity**. The assistant uses the existing private `GEMINI_API_KEY`.
The optional `HARVEST_AI_MODEL` selects its model independently; it defaults to
`gemini-3.1-flash-lite`. Clear configuration after changing `.env`.

Gemini receives the declared olive quantity, harvest date and method, variety,
governorate and irrigation type. Names, contact details, exact coordinates and
free-text notes are excluded. A positive recorded quantity is required.

Gemini estimates a range of recovered oil yield by mass. Laravel validates that
range and converts it to liters: olive kg × yield percent / 100 / 0.916. The
0.916 kg/L density is an assumption shared with the distribution estimator.
The result covers the whole declared harvest, rather than a partial mill request.

The range is advisory, not measured output or a quality grade. Maturity, fruit
moisture, storage and extraction efficiency can change actual results. Estimates
are displayed temporarily and never create or change an oil lot. Millers continue
to record actual output for each lot. Owner/admin authorization and a five-request
per-minute limit apply; provider failures show a message instead of fabricated data.

Automated checks: `php artisan test --filter HarvestOilAssistantTest`.
