# OliveTrace — Module Summary (for Mariem)

## Overview
This module implements the **traceability flow from harvest to oil lot** in the OliveTrace Laravel 12 application.

---

## Implemented Steps (from `opencode.txt` spec)

| Step | Feature | Status |
|------|---------|--------|
| 1-2 | User auth, roles, farms, mills | ✅ Pre-existing |
| **3** | **Harvest declaration** | ✅ Done |
| **4** | **Mill request (producer → mill)** | ✅ Done |
| **5** | **Miller workflow (accept/refuse/complete)** | ✅ Done |
| **6** | **OilLot creation by miller** | ✅ Done |
| **7** | **Producer reads oil origin** | ✅ Done |

---

## 1. Harvest (Step 3)

### Data
- **Table**: `harvests` (soft deletes)
- **Key fields**: `farm_id`, `harvest_date`, `expected_end_date`, `method` (enum), `quantity_kg` (nullable), `status` (enum), `notes`

### Enums
- `HarvestMethod`: 9 tools (hand_picking, manual_comb, pneumatic_comb, vibrating_pole, branch_shaker, trunk_shaker, harvest_platform, vacuum_harvester, hand_shears)
- `HarvestStatus`: `declared` | `in_progress` | `completed` | `milled` (locked)

### Features
- Producer declares harvest on their **active farms only**
- `quantity_kg` **nullable** — unknown quantity supported
- `expected_end_date` allows scheduling mill while harvest running
- Farm show page: "Add harvest" button + harvest list
- Sidebar: "My harvests" (producer), "Harvests" (admin)

### Authorization
- Owner (producer) CRUD
- Admin read/delete (moderation)
- Delete blocked if mill requests exist
- `milled` status locks updates

---

## 2. Mill Request (Step 4)

### Data
- **Table**: `mill_requests` (no soft deletes)
- **Key fields**: `harvest_id`, `mill_id` (nullable), `external_mill_name` (nullable), `requested_date`, `appointment_date`, `quantity_kg`, `status`, `message`, `response_message`

### Enum
- `MillRequestStatus`: `pending` | `accepted` | `refused` | `cancelled` | `completed`

### Business Rules
- Exactly one of `mill_id` OR `external_mill_name`
- One active request per harvest (`pending`/`accepted`)
- Requested quantity ≤ harvest remaining quantity (skipped if harvest qty unknown)
- Producer can cancel while `pending`

### Routes
| Role | Routes |
|------|--------|
| Producer | `producer.harvests.*`, `POST harvests/{harvest}/requests`, `PATCH requests/{id}/cancel` |
| Admin | `admin.harvests.index/show/destroy` |

---

## 3. Miller Workflow (Step 5)

### Routes (`mill.*` middleware: auth + role:miller)
| Route | Purpose |
|-------|---------|
| `GET mill/requests` | List requests to their mill (filter by status/search) |
| `GET mill/requests/{id}` | Detail + status update form |
| `PATCH mill/requests/{id}` | Update status, appointment, response message |

### Status Transitions (Miller)
- `pending` → `accepted` (sets appointment)
- `pending` → `refused` (adds reason in `response_message`)
- `accepted` → `completed` (auto-sets harvest status = `milled`, redirects to OilLot create)

---

## 4. OilLot (Step 6)

### Data
- **Table**: `oil_lots`
- **Fields**: `mill_request_id` (FK), `lot_number` (unique, format `LOT-YYYY-NNN`), `liters`, `quality_grade` (enum), `production_date`, `notes`

### Enum
- `OilQuality`: `extra_virgin` | `virgin` | `lampante`

### Creation Flow
1. Miller marks request `completed`
2. Redirect → `GET mill/oil-lots/{request}/create`
3. Miller fills: lot number, liters, quality, production date, notes
4. `POST mill/oil-lots/{request}` → creates OilLot
5. Redirect back to mill request show (now shows OilLot card with edit link)

### Authorization
- Miller: create/edit own mill's oil lots (only when request `completed`)
- Producer: read-only view of lots from their harvests
- Admin: full access

---

## 5. Producer Traceability (Step 7)

### Routes
- `GET producer/oil-lots` — paginated list with search + quality filter
- `GET producer/oil-lots/{id}` — detail view

### Detail Shows
- Lot number, quality badge, volume, production date
- Mill name, appointment date
- Harvest link (qty, method, date)
- Farm name, governorate
- Harvest notes
- Mill communication (producer message + mill response)

---

## Key Technical Decisions

| Area | Decision |
|------|----------|
| `quantity_kg` nullable | Allows unknown harvest quantity; budget check skipped |
| Two status systems | `HarvestStatus` (producer) + `MillRequestStatus` (milling) |
| `expected_end_date` | Enables scheduling mill while harvest in progress |
| `response_message` | Extra field for miller accept/refuse notes |
| No soft deletes on `mill_requests` | Audit trail required |
| `milled` locks harvest | Prevents post-milling edits |
| Miller owns exactly one mill | `mills.user_id` unique + NOT NULL |

---

## Files Created (New)

### Enums
- `app/Enums/HarvestMethod.php`
- `app/Enums/HarvestStatus.php`
- `app/Enums/MillRequestStatus.php`
- `app/Enums/OilQuality.php`

### Models
- `app/Models/Production/Harvest.php`
- `app/Models/Production/MillRequest.php`
- `app/Models/Production/OilLot.php`

### Policies
- `app/Policies/HarvestPolicy.php`
- `app/Policies/MillRequestPolicy.php`
- `app/Policies/OilLotPolicy.php`

### Repositories / Services
- `app/Repositories/Production/Harvests.php`
- `app/Services/Production/HarvestManagement.php`

### Form Requests
- `app/Http/Requests/Production/HarvestRequest.php`
- `app/Http/Requests/Production/MillRequestRequest.php`

### Controllers
- `app/Http/Controllers/Production/HarvestController.php`
- `app/Http/Controllers/Production/MillRequestController.php`
- `app/Http/Controllers/Production/OilLotController.php`
- `app/Http/Controllers/Miller/MillRequestController.php`
- `app/Http/Controllers/Miller/OilLotController.php`

### Factories / Seeders
- `database/factories/Production/HarvestFactory.php`
- `database/factories/Production/MillRequestFactory.php`
- `database/factories/Production/OilLotFactory.php`
- `database/seeders/HarvestSeeder.php`
- `database/seeders/MillRequestSeeder.php`
- `database/seeders/OilLotSeeder.php`

### Migrations
- `2026_10_07_210000_create_harvests_table.php`
- `2026_10_07_210010_create_mill_requests_table.php`
- `2026_10_07_224013_make_harvest_quantity_nullable.php`
- `2026_10_07_224816_create_oil_lots_table.php`

### Views
- `resources/views/production/harvests/index.blade.php`
- `resources/views/production/harvests/create.blade.php`
- `resources/views/production/harvests/edit.blade.php`
- `resources/views/production/harvests/show.blade.php`
- `resources/views/production/oil-lots/index.blade.php`
- `resources/views/production/oil-lots/show.blade.php`
- `resources/views/miller/mill-requests/index.blade.php`
- `resources/views/miller/mill-requests/show.blade.php`
- `resources/views/miller/oil-lots/create.blade.php`
- `resources/views/miller/oil-lots/edit.blade.php`
- `resources/views/production/farms/show.blade.php` (updated)

### Routes
- `routes/production.php` (harvests, mill-requests, oil-lots)
- `routes/web.php` (miller mill-requests, oil-lots)

---

## Files Modified (Existing)

| File | Changes |
|------|---------|
| `app/Models/User.php` | Added `isActiveProducer()`, `isActiveMiller()`, `isActiveAdmin()` |
| `app/Models/Production/Farm.php` | Added `harvests()` relation |
| `app/Models/Mill.php` | Added `millRequests()` relation |
| `app/Providers/AppServiceProvider.php` | Registered policies (Harvest, MillRequest, OilLot) |
| `app/Http/Controllers/Controller.php` | Added `AuthorizesRequests` trait |
| `app/Http/Controllers/Production/FarmController.php` | `show()` passes harvests to view |
| `resources/views/layouts/admin.blade.php` | Sidebar: "My harvests", "Harvests", "My oil lots", "Mill requests" |
| `resources/views/components/icon.blade.php` | Added `harvest` icon |

---

## Demo Data (after `php artisan db:seed`)

| Entity | Count | Notes |
|--------|-------|-------|
| Farms | 3 | All belong to demo producer |
| Harvests | 3 | 1 `in_progress`, 1 `completed`, 1 `milled` |
| Mill Requests | 3 | 1 accepted (demo mill), 1 pending (external), 1 completed → OilLot |
| Oil Lots | 1 | LOT-2026-xxx, ~200L, extra_virgin |
| Mills | 4 | 1 owned by demo miller (user 3) |
| Users | 9 | admin, producer, miller, consumers |

---

## Verification Commands

```bash
# Run migrations + seed
php artisan migrate:fresh --seed

# Lint (Pint)
php vendor/bin/pint --test

# Route list
php artisan route:list --name=harvest
php artisan route:list --name=mill
php artisan route:list --name=oil
```

---

## Access Matrix

| Feature | Producer | Miller | Admin | Consumer |
|---------|----------|--------|-------|----------|
| Declare harvest | ✅ own farms | ❌ | ✅ read/delete | ❌ |
| Send mill request | ✅ | ❌ | ❌ | ❌ |
| Cancel request | ✅ (pending) | ❌ | ❌ | ❌ |
| View mill requests | ❌ | ✅ own mill | ✅ all | ❌ |
| Update request status | ❌ | ✅ own mill | ❌ | ❌ |
| Create OilLot | ❌ | ✅ (completed only) | ❌ | ❌ |
| View OilLot | ✅ own | ✅ own mill | ✅ all | ❌ |

---

## Next Steps (Not in Scope)
- OilLot editing by producer (corrections)
- Lab analysis attachment to OilLot
- Bottling / packaging traceability
- Consumer QR code scan → origin page