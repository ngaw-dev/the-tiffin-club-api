I'll grill you on this brief round by round until we've settled the design tree. No `GLOSSARY.md` exists yet — I'll capture terms as we resolve them.

**Round 1 — the frontier right now:**

❓ **Q1** - **Cut off time**: The brief says "ordering open 09:00–cut off time" but never states the cut off time, or whether it varies. Is it a fixed daily time (e.g. 11:00), per-day configurable by staff, or same-day-only (order any time before 23:59)?

Global cut off time fallback if no cutoff time specified
---

❓ **Q2** - **Delivery details**: Pickup or delivery are both offered. What data does a delivery order require (address, phone?), and is there a delivery fee or zone restriction?

Wellingon region only, free delivery
---

❓ **Q3** - **What's in an order**: "One main meal per day with optional extras." Is an order exactly **one** main (quantity 1?), or can a customer order N mains for a family? Can extras have quantities?

N main, and N extras
---

❓ **Q4** - **Manage token lifetime**: The 64-hex manage token is the only way to edit/cancel. Is it issued once at order creation and never expires (order history = "keep the link"), or does it expire?

expire at end of the day
---

❓ **Q5** - **Optional account value**: Guest-first with an optional email/password account. What does the account actually _do_? See past orders? Prefill details? Nothing yet (future-proofing)?

account to see orders, prefill details
---

❓ **Q6** - **Order states**: After cutoff, what are the terminal states? E.g. `placed → (edited*) → cancelled | fulfilled`. Is there any staff-side state transition (mark as picked up / delivered), or does the order just sit as "placed" forever?

placed
order update (customer)
order update (staff)
order update accepted
order update cancelled
ready for pickup
out for delivery
completed
---

❓ **Q7** - **Staff token model**: "Token-guarded console." One shared staff token in config, or per-staff tokens with possible roles? And is the staff API also versioned (`/api/v1/staff/...`)?

ignore staff atm
---

❓ **Q8** - **Cutoff day boundary**: Can customers order for _tomorrow_, or strictly today's menu only (menu published 09:00 same day)?

strictly today
