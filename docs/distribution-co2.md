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
