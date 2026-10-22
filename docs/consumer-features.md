# Consumer features (Aymen)

This integration uses the latest pushed `feature/aymen` version alongside the
newer producer, milling, laboratory and distribution work. It preserves the
shared CI workflow and Mariem's notifications and milling operations.

## What is included

- Public trace lookup at `/trace`: product slug, ID, name, brand or lot number.
  Search and recommendations use visible, unarchived products only.
- Optional camera QR scanner. Allow camera access on HTTPS or localhost. If
  the camera or scanner CDN is unavailable, use the manual search field.
- Consumer dashboard: the signed-in consumer's three latest reviews and
  complaints, plus the three highest-rated public products.
- Existing review management and private complaint tracking.
- Optional JPEG/PNG/WebP complaint evidence, up to 2 MB. New uploads are stored
  on the private local disk; only the complaint owner and an active admin can
  retrieve them through the authorized evidence route.
- Admin AI complaint reply drafts and visible-review summaries using Groq.
  The draft does not change the complaint decision or send a reply. The admin
  reviews/edits it and saves the final response explicitly. Requests are limited
  to six per minute per admin and endpoint.
- An email after a changed complaint status or response. Re-saving an unchanged
  decision does not send another message. With `MAIL_MAILER=log`, messages go to
  `storage/logs/laravel.log`; configure your own SMTP credentials privately for
  actual delivery.
- Recovered **My OliveTrace** page at `/my-trace`: personal reviews,
  complaints, purchase proofs and notifications. Notification forms mark only
  the signed-in consumer's alerts as read.
- Recovered **complaint SLA dashboard** at `/admin/consumer/sla`: status totals,
  overdue counts, resolution times, periods, daily trend and admin workload.
  New complaints get a 48-hour deadline. Reopening a closed complaint into
  `in_review` increments its reopen count and starts a new 48-hour deadline.
  Existing complaints without a deadline get one when first moderated.
- Recovered private purchase-proof workflow. Submit a receipt/invoice from a
  trace page or My OliveTrace; an admin downloads/reviews it under **Purchase
  proofs** and approves it. An upload alone never grants a verified-purchase
  badge. Replacing the proof removes approval and requires another review.
- Recovered review edit history (including updates from the trace form),
  displayed to admins alongside the original words and ratings.
- Recovered admin review-reply drafts with professional, empathetic, formal
  or friendly tone, using the same Groq configuration. There is no generated
  confidence score or template pretending to be AI. A missing/unavailable
  provider returns an error and leaves manual moderation available.
- Recovered product share cards and QR codes generated from the public product.
  Certification status comes from the laboratory records; it is not assumed.
- Event-driven alerts for changed complaint statuses, priority complaints and
  new valid certificates for reviewed products. Priority classification is the
  existing offline keyword helper, not an LLM. Admin urgent alerts appear on
  the SLA dashboard. These alerts do not replace the milling notifications.

## Update an existing checkout

Keep your own `.env` and database. After checking out the integrated branch:

```powershell
composer install
npm.cmd ci
php artisan optimize:clear
php artisan migrate
npm.cmd run build
php artisan olivetrace:check-database
php artisan serve
```

The migrations add complaint photos/AI drafts, review edit history, SLA fields,
purchase proofs and approvals, consumer notifications and saved review replies.
Do not run `migrate:fresh` to update. If you previously ran experimental Aymen
migrations that were later removed from his branch, inspect your migration
status before updating; do not delete or recreate your database automatically.
Photos uploaded on the old version's public disk are not moved by this schema
migration. Keep a backup and move those files to the private disk before using
them with this version, then remove the public copy after verifying access.

## Optional Groq setup

Add your own values to the private `.env` only:

```dotenv
GROQ_API_KEY=
CONSUMER_AI_MODEL=llama-3.3-70b-versatile
```

Then run `php artisan config:clear`. These settings are separate from
`GEMINI_API_KEY`, `FARM_AI_PROVIDER` and `FARM_AI_MODEL`. A Gemini key cannot be
used as a Groq key. Without Groq configuration or during a provider failure,
admins can still handle complaints and reviews manually.

The reply assistant sends the product name, complaint subject/description and
classification to Groq. It does not send the evidence photo or account contact
fields. The summary sends up to 25 visible reviews with product names/ratings;
hidden reviews are excluded. AI responses are displayed as escaped text.

## Quick check

1. Open **Trace a bottle** and search for an existing public product or lot.
2. Sign in as a consumer, submit a complaint with a small PNG/JPEG and open it.
   Another consumer must not be able to view the complaint or its photo.
3. Open the consumer dashboard: personal complaints/reviews should appear.
4. Sign in as admin, open **Complaints**, view the photo, request an AI draft
   (requires your Groq key), edit the response and save a decision.
5. Check the consumer's complaint status, My OliveTrace notification and log
   mail message. On the
   admin Reviews page, request a visible-review summary (also requires Groq).
6. Submit a private purchase proof, review and approve it as admin, then check
   the verified badge. Replace it and confirm approval is cleared.
7. Change a review through both its edit page and the trace form; inspect edit
   history as admin. Open the SLA dashboard and test its period filter. Open a
   product's share page and check its QR code and real certification status.

Development test accounts and password are listed in the README. Use them only
on a local development installation.

`tests/Feature/ConsumerAdvancedTest.php` and
`tests/Feature/ConsumerRecoveredFeaturesTest.php` cover the integration behavior,
including access control, validation, provider failures and rate limits.
Automated AI tests mock HTTP; they do not prove a live API key works. The camera
scanner also requires a manual check on a device with a camera.

The older features removed in Aymen's final push have been recovered from his
earlier commits and repaired against the current team version. Your colleague's
original commits remain in Git history. The legacy `getScannedProducts()` helper
lists products associated with the consumer's reviews/complaints; it does not
record actual camera scan history.
