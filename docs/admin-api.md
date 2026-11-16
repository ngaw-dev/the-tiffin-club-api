# Admin API

Token-authenticated API for the admin SPA. All routes are prefixed with `/api/v1/admin`.

## Authentication

Sanctum personal access tokens. Access is limited to the staff email allowlist (`ADMIN_EMAILS` env / `config/staff.php`) — the same allowlist as the legacy staff web panel.

```
POST /api/v1/admin/login      { "email": "...", "password": "..." }  → { "data": { "token": "1|...", "user": { "name", "email" } } }
POST /api/v1/admin/logout     (Bearer token)                        → deletes the current token
```

All other endpoints require `Authorization: Bearer <token>` and return **401** without it, **403** if the account is not on the allowlist.

## Conventions

- Responses use the app's standard envelope: `{ success, message, data }`; lists are `{ data: { resource, links, meta } }`.
- List endpoints support Spatie Query Builder params: `?filter[...]=`, `?sort=` (prefix `-` for descending), `?page=`, `?per_page=` (default 15).
- All money amounts are **integer cents** — in both requests and responses, including `POST /payments`.

## Orders

| Method | Path | Notes |
|---|---|---|
| GET | `/orders` | Filters: `status`, `fulfilment`, `customer_id`, `menu_id`. Sorts: `placed_at`, `total`. Default `-placed_at`. Includes customer + payments (with `paid` sum). |
| GET | `/orders/{id}` | Includes items, payments, customer. |
| PATCH | `/orders/{id}` | `{ "status": "..." }` — status only. Valid: `confirmed`, `ready_for_pickup`, `out_for_delivery`, `completed`, `cancelled`. 422 if cancelling an order with a recorded paid sum > 0 (payments are immutable; see ADR 0002). Order contents are immutable. Writes an audit entry (`status_changed`, actor `staff`). |

## Payments

| Method | Path | Notes |
|---|---|---|
| GET | `/payments` | Filters: `method`, `order_id`, `customer_id`. Sorts: `paid_at`, `amount`. Default `-paid_at`. |
| POST | `/payments` | `{ "order_id": 1, "amount": 2000 }` — records a manual cash payment (cents). 422 if the amount exceeds the order's outstanding balance. |

Refunds / marking payments failed are not supported.

## Customers

| Method | Path | Notes |
|---|---|---|
| GET | `/customers` | Filters: `name`, `email`, `suburb`. Sort: `name`. Includes `order_count`. |
| GET | `/customers/{id}` | Includes orders (with payments), newest first. |
| PATCH | `/customers/{id}` | Any of `name`, `email`, `phone`, `address`, `suburb`. Email is lowercased, phone normalized to digits. No create/delete/blocking. |

## Menus

| Method | Path | Notes |
|---|---|---|
| GET | `/menus` | Filter: `menu_date`. Sort: `menu_date`. Default `-menu_date`. Includes items. |
| POST | `/menus` | `{ "menu_date": "YYYY-MM-DD", "cutoff_time": "HH:MM"? }` — unique date. |
| GET | `/menus/{id}` | Includes items. |
| PATCH | `/menus/{id}` | `menu_date`, `cutoff_time`, and/or `publish: true/false` (sets/clears `published_at`). |
| DELETE | `/menus/{id}` | Deletes the menu and its items. **409 Conflict** if any order references the menu. |
| POST | `/menus/{id}/items` | `{ type, name, description?, price, sort_order? }` — `type`: `main` \| `extra`; `price` in cents. |
| PATCH | `/menus/{id}/items/{itemId}` | Same fields, all optional. |
| DELETE | `/menus/{id}/items/{itemId}` | Safe for existing orders — `order_items` snapshot `unit_price`. |

## Global Settings

| Method | Path | Notes |
|---|---|---|
| GET | `/settings` | Same shape as public `GET /v1/settings`. |
| PUT | `/settings` | Partial update of any known key: `delivery_suburbs` (array), `order_cutoff_time`, `pickup_start_time`, `pickup_end_time` (`HH:MM`), `store_phone`, `store_phone_alternate`, `store_email` (email), `pickup_address`, `pickup_map_url` (url). Unknown keys are rejected. |

## Notes

- The legacy staff web panel (`/staff/*`) remains in place and untouched; its settle form still submits dollars (admin API is cents-only).
- The Filament panel at `/admin` is unrelated to this API (`/api/v1/admin`).
- Payment records are immutable — no edit/refund/delete/fail endpoints, by design (ADR: `docs/adr/0002-payments-are-immutable.md`).
- The cancel block applies to the admin API; the customer token endpoints are governed by the same ADR.
- Tests: `tests/Feature/AdminApiTest.php`.
