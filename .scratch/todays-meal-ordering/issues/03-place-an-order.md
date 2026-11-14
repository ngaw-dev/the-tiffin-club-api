# 03: Place an order

**What to build:** A customer (guest, no account needed) can place an order for today's meal: N mains and N extras, name + email always, phone + Wellington suburb (validated against a fixed suburb list) required for delivery. The server validates every item against today's menu, computes the Order Total at menu prices, gates placement on the ordering window, and returns an opaque 64-hex Manage Token as the sole handle on the order.

**Blocked by:** 01 (Today's menu read path), 02 (Ordering window service).

**Status:** done

- [x] `POST /api/v1/orders` accepts mains/extras with quantities, validates against today's published menu
- [x] Pickup or free delivery; delivery requires phone + valid Wellington suburb
- [x] Server-computed Order Total returned; payment never charged online
- [x] 64-hex Manage Token issued at creation
- [x] Placement rejected outside the ordering window
- [x] Feature tests cover happy path, invalid item, closed window, delivery validation
