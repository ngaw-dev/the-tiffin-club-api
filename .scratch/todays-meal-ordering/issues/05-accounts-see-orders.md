# 05: Accounts see orders

**What to build:** A customer can optionally create an email/password account and log in. An account sees its past orders — including guest orders placed earlier with the same email, linked retroactively by email match — and can fetch prefill details (name, email, phone, suburb) for new orders.

**Blocked by:** 03 (Place an order).

**Status:** ready-for-agent

- [ ] Register/login with email + password; token-based auth
- [ ] `GET /api/v1/account/orders` lists orders linked by email match, including pre-account guest orders
- [ ] Prefill details endpoint returns most recent order's contact/fulfilment details
- [ ] Ordering stays guest-first: no account required anywhere
