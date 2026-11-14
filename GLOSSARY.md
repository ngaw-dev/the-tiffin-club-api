# Glossary

- **Menu**: The single meal offering for today, published at 09:00 Pacific/Auckland. Exactly one menu per calendar day. Main = name, description, price; Extras = name, price. Ordering opens at max(09:00, publish time); before publication the store reads as closed.
- **Main**: The primary meal item on today's menu. A customer may order N mains.
- **Extra**: An optional add-on item on today's menu. A customer may order N of each extra.
- **Ordering Window**: The period, computed server-side in Pacific/Auckland, during which a customer may place, edit, or cancel an order. Opens at 09:00 when the menu is published; closes at the Cutoff Time.
- **Cutoff Time**: The per-day time after which no order may be placed, edited, or cancelled. Falls back to a global default when none is set for the day.
- **Manage Token**: An opaque 64-hex-character token issued at order creation; the sole authority to view, edit, or cancel that order. Expires at 23:59:59 Pacific/Auckland on the order day; expired tokens yield a "no longer available" response.
- **Order**: A customer's request for today's meal (mains, extras, fulfilment method, contact/delivery details). Exists only for today's menu. Lifecycle: `placed → (edited)* → cancelled | ready for pickup / out for delivery → completed`. Customer edits and cancellations are free until the Cutoff Time; after it, only staff may cancel. Edits mutate the order in place, with an append-only audit trail.
- **Fulfilment Method**: Either pickup, or free delivery within the Wellington region (validated against a fixed suburb list).
- **Order Total**: The server-computed sum of mains and extras at menu prices. Shown to the customer, collected in person — never charged online.
- **Contact Details**: Every order carries name + email; phone is required for delivery orders only.
- **Account**: Optional email/password credential. Sees past orders and prefills details. Orders link to an account by email match — including guest orders placed before the account existed.
- **Guest**: A customer who orders without an account, identified only by their Manage Token.
