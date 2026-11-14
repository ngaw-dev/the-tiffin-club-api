# 04: Manage an order by token

**What to build:** A customer holding the Manage Token can view, edit, or cancel their order. Edits and cancellations are free until the Cutoff Time (same server-side gate as placement), mutate the order in place, and append to an audit trail. After cutoff the order is locked to the customer. The token expires at 23:59:59 Pacific/Auckland on the order day; expired tokens get a "no longer available" response. Customer API rate-limited per IP.

**Blocked by:** 03 (Place an order).

**Status:** ready-for-agent

- [ ] `GET /api/v1/orders/{token}` returns order with total and lifecycle state
- [ ] `PATCH /api/v1/orders/{token}` edits items/details until cutoff, in place, with append-only audit trail
- [ ] `DELETE /api/v1/orders/{token}` cancels until cutoff
- [ ] Expired token returns "no longer available", not a bare 404
- [ ] Order lifecycle states: placed → ready for pickup / out for delivery → completed, plus cancelled
- [ ] Per-IP rate limiting on customer endpoints
