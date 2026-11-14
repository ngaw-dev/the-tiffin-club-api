# The Tiffin Club

Today's meal, ordered simply. A single-restaurant daily tiffin ordering platform: customers see today's menu, order pickup or delivery, and pay in person. Staff manage the daily menu and orders from a token-guarded console.

## Top-level decisions

- **Single restaurant, not a marketplace.** One main meal per day with optional extras. No stock counts, no sold-out states, no online payment.
- **Server-authoritative time.** All ordering cutoffs are computed in Pacific/Auckland on the server; the client clock is never trusted. Every time comparison funnels through a single ordering-window service.
- **Ordering window.** Menu published at 09:00; ordering open 09:00–cut off time; "closing soon" messaging in the last 60 minutes; closed after cut off time.
- **Customer identity is guest-first.** No account required; an optional email/password account exists. Orders are managed via an opaque 64-hex manage token, never a human-readable order reference.
- **Order editing until cutoff.** Customers may update or cancel any order detail up to the cut off time cutoff; the same server-side gate guards edits and cancellations.
- **Two API surfaces.** A versioned customer REST API (menu + order CRUD, rate-limited) and a staff API guarded by a staff token middleware for today's menu and order history.
- **Layered API architecture.** Routes → middleware → controllers → form requests → services → models. Controllers stay thin; business logic lives in three services (menu, order, ordering window).
