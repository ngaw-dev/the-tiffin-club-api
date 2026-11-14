**Round 3 — remaining frontier:**

❓ **Q1** - **Contact details on an order**: What does every order capture? Name + phone for pickup too, or is phone delivery-only?

➡️ Recommended: name + email on every order (email for receipts/linking), phone required for delivery only.

---

❓ **Q2** - **Menu content**: Does today's menu carry a price for main and extras (customer pays in person, so total is shown but not charged)? And a description/photo?

➡️ Recommended: main = name + description + price; extras = name + price. Total computed server-side and shown; payment collected in person.

---

❓ **Q3** - **Menu publication timing edge**: If staff haven't published by 09:00, is the store just "closed / menu not yet up", and can a late menu still be published (ordering opens on publish if after 09:00)?

➡️ Recommended: yes — ordering opens at max(09:00, publish time); before publication the customer API reports the store as closed with "menu coming soon".

---

❓ **Q4** - **Day rollover for existing tokens/orders**: At midnight, are yesterday's orders simply historical (queryable by staff later, per the brief's "order history") while manage tokens die? Any end-of-day batch job?

➡️ Recommended: no batch job. Tokens are checked against their order's date at request time; old orders persist read-only for staff. (Ignoring staff API for now, but the data model keeps them.)

---

❓ **Q5** - **Rate limiting scope**: Brief says customer API is rate-limited. Per-IP? Per-token? Rough budget?

➡️ Recommended: per-IP (Laravel throttle middleware), modest budget (e.g. 60/min), no per-user tracking.

---

❓ **Q6** - **API versioning surface**: `/api/v1/...` for customer endpoints — confirm the concrete resources: `GET /menu/today`, `POST /orders`, `GET/PATCH/DELETE /orders/{token}`?

➡️ Recommended: exactly those four, token in the URL path (it _is_ the identifier).
