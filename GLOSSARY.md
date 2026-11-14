# Glossary

- **Menu**: The single meal offering for today, published at 09:00 Pacific/Auckland. Exactly one menu per calendar day. Main = name, description, price; Extras = name, price. Ordering opens at max(09:00, publish time); before publication the store reads as closed.
- **Main**: The primary meal item on today's menu. A customer may order N mains.
- **Extra**: An optional add-on item on today's menu. A customer may order N of each extra.
- **Ordering Window**: The period, computed server-side in Pacific/Auckland, during which a customer may place, edit, or cancel an order. Opens at 09:00 when the menu is published; closes at the Cutoff Time.
- **Cutoff Time**: The per-day time after which no order may be placed, edited, or cancelled. Set per menu; falls back to the Global Settings default when none is set for the day.
- **Global Settings**: The store's single operator-editable settings row: the Delivery Suburb list, the default Cutoff Time, and the Pickup Window. Read-only to the API; changed directly by the operator.
- **Manage Token**: An opaque 64-hex-character token issued at order creation; the sole authority to view, edit, or cancel that order. Expires at 23:59:59 Pacific/Auckland on the order day; expired tokens yield a "no longer available" response.
- **Order**: A customer's request for today's meal (mains, extras, fulfilment method, contact/delivery details). Exists only for today's menu. Lifecycle: `placed → (edited)* → cancelled | ready for pickup / out for delivery → completed`. Customer edits and cancellations are free until the Cutoff Time; after it, only staff may cancel. Edits mutate the order in place, with an append-only audit trail. Each order snapshots the customer's name and delivery address at placement, and may carry delivery notes. Email customers receive a confirmation email at placement; phone-only customers get none. Both outcomes are recorded in the audit trail by the system actor.
- **Fulfilment Method**: Either pickup, or free delivery within the Wellington region (validated against the Delivery Suburb list). All zones are free.
- **Pickup Window**: The informational start/end times during which pickup orders may be collected. Shown to the customer; not validated or enforced.
- **Order Total**: The server-computed sum of mains and extras at menu prices. Shown to the customer, collected in person — never charged online.
- **Order Lookup**: A knowledge-based check — the Manage Token plus the email or phone used at checkout — that returns the order only when the contact matches. Every failure (bad token, expired token, wrong contact) answers identically.
- **Contact Details**: Every order is placed with a name plus at least one of email or phone; these identify the Customer. Email is stored lowercase, phone normalized.
- **Customer**: The person placing orders. Identified by email or phone — at least one must be present, each unique when present. Carries the default delivery address. Found or created silently at checkout from the submitted contact details; when an order's email and phone match different customers, the email match wins.
- **Account**: Optional email/password credential. Sees past orders and prefills details. An Account belongs to a Customer; orders link to the Customer directly.
- **Guest**: A customer who orders without an account, identified only by their Manage Token.
