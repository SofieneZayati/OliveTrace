# Module workflow rules

These rules apply to the integrated team project.

## Oil products and lots

A producer's create/edit form offers only lots owned by that producer. Server validation
also checks ownership, including when a user submits an ID directly. Admins can inspect
and correct products through the existing admin routes.

One oil lot can supply several products, such as 250 ml and 750 ml bottles. Using a lot
for one product does not remove it from the selection list. Existing shipment history
still prevents changing a product's source lot.

`OilLotLookup::available(?int $producerUserId = null)` returns lot summaries keyed by
ID. Pass the producer's user ID for producer forms; omit it for an unrestricted lookup.
The optional argument must be present in any replacement implementation of this contract.

## Certification requests

Only a lot's producer can submit a request. A lot may have at most one pending request,
and an approved request blocks another submission. The form and POST action enforce the
same rule. Submission locks the lot inside a database transaction so simultaneous
submissions cannot create duplicate pending requests.

A rejected lot can be submitted again. Previous requests and analyses remain available
as history. Requests record their submission time explicitly.

Laboratory processing locks and reloads the request in its transaction. A second submission,
including one with a stale route-bound model, cannot create another analysis or certificate.
The certificate type reflects the oil lot's recorded grade; the application does not
automatically classify oil as extra virgin from a single measurement. The laboratory's
approval/rejection remains a manual decision.

## Public certificates

The public certificate page agrees with the catalog's verification rules: the request
must be approved, the certificate must have an accepted active status, its issue date
must have arrived, and its expiry date must not have passed. The expiry date is inclusive.
Revoked, expired, future-issued and rejected records are not shown as valid.

Missing laboratory results display an unavailable message instead of causing a page error.
A measured peroxide value of zero is displayed. Private laboratory notes are not displayed.

## Verification

Regression tests cover repeated submissions, rejected-request retries, other producers'
lots, stale laboratory submissions, grade preservation, certificate date/status boundaries,
and several bottle sizes from the same lot. Run both database suites:

```sh
php artisan test
php scripts/test-mysql.php
```

The MySQL suite uses the separate database ending in `_testing`. It must never run on
the development database. No new migrations are required for these workflow fixes.
