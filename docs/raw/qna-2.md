**Round 2 — the frontier that your answers opened:**

❓ **Q1** - **What does "order update" mean?** Your lifecycle lists `order update (customer)`, `order update (staff)`, `order update accepted`, `order update cancelled`. Is an "order update" a **requested change** that staff must accept (a two-phase amendment), and does the customer's direct edit-until-cutoff from the brief still exist alongside it — or did the two-phase flow *replace* direct customer edits?

➡️ Recommended: keep the brief's model — customers edit freely until cutoff, no acceptance step. "Order update (staff)" = staff can amend any order any time; "order update accepted/cancelled" sound like notification statuses, not a workflow. Simplify to: `placed → (edited)* → cancelled | ready for pickup | out for delivery → completed`.

---

❓ **Q2** - **Wellington-only delivery, enforcement**: Free delivery within the Wellington region. How is "in region" decided — free-text address trusted as-is, a suburb dropdown from a fixed list, or nothing server-side (front-end honour system)?

➡️ Recommended: a fixed suburb list on today's menu context (or config); customer picks a suburb; server validates delivery orders against it. Cheap to enforce, no address geocoding.

---

❓ **Q3** - **"End of day" for token expiry**: Midnight Pacific/Auckland? Same as cutoff time? What happens to a customer holding an expired token — 404 vs "order locked" message?

➡️ Recommended: token valid until 23:59:59 Pacific/Auckland of the order day; expired token returns a "no longer available" response (not 404-as-unknown) so the customer isn't confused.

---

❓ **Q4** - **Edit semantics**: When a customer edits before cutoff, do we mutate the order in place (history lost) or keep an immutable order + revisions?

➡️ Recommended: mutate in place, keep an append-only `order_updates` audit table. No revision model.

---

❓ **Q5** - **Account ↔ order linking**: For "account sees past orders" — are guest orders linked to an account retroactively (by email match at order time), or only orders placed *while logged in* count?

➡️ Recommended: orders are linked by matching the order's email to the account email, at order creation and at account creation (so yesterday's guest orders appear when you later sign up / from the same email).

---

❓ **Q6** - **Cancellation rights**: Can the *customer* cancel right up to the cutoff (brief says yes)? And after cutoff, only staff can cancel?

➡️ Recommended: yes — customer cancel until cutoff; after cutoff, staff-only cancellation.