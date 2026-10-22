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

The new migration adds `evidence_photo` and `ai_draft_reply` to `complaints`.
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
5. Check the consumer's complaint status and the log mail message. On the
   admin Reviews page, request a visible-review summary (also requires Groq).

Development test accounts and password are listed in the README. Use them only
on a local development installation.

`tests/Feature/ConsumerAdvancedTest.php` covers the new integration behavior,
including access control, validation, provider failures and rate limits.
Automated AI tests mock HTTP; they do not prove a live API key works. The camera
scanner also requires a manual check on a device with a camera.

The older My OliveTrace dashboard, SLA dashboard, verified-purchase workflow,
share cards and consumer notification system were removed in Aymen's final
pushed commit and are not part of this snapshot. Their commits remain in Git
history. The shared milling notifications remain available.
