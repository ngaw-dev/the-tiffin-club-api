# ADR 0002: Payments are facts of history

- **Status**: Accepted
- **Date**: 2026-11-16

## Context

The Tiffin Club is a same-day, pay-in-person cash business. Orders are small, and staff
record payments by hand through the admin API (`POST /v1/admin/payments`, method `cash`).
The obvious future request is "add refunds" or "let me mark a payment as failed" —
payment mutation.

Related decision: an Order with a paid sum > 0 cannot be cancelled through the API (422).
Cancelling a paid order would orphan money records or silently imply a refund.

## Decision

Payments are facts of history. The admin API supports creating payment records only.
There is no endpoint to edit, refund, delete, or fail a payment, and an order with a
recorded paid sum greater than zero cannot be cancelled through any API endpoint
(admin or customer). Corrections happen manually outside the API, handled case-by-case
by the operator with developer assistance — no automated mechanism exists.

All money is exchanged as **integer cents** in every API request and response, including
`POST /payments`.

## Consequences

- Refund/void workflows require a conscious reversal of this ADR, not an incremental endpoint.
- The `cancelled` status is reachable only for orders with a paid sum of zero, on both the
  admin and customer endpoints; the customer-facing 422 wording gives no internal detail.
- Staff friction in edge cases is accepted deliberately: the most common corrections
  (refund, wrong amount fat-finger) all require the manual path. The scale of the business
  does not justify the complexity and audit risk of mutable money records.
- The legacy staff web panel's settle form still submits dollars and converts; the admin
  API is cents-only and does not.

## Alternatives rejected

- Mutable payments with an audit trail: rejected as complexity without payoff at this scale.
- Offset ("negative") payments: rejected — makes "any payment record" mean something
  ambiguous, and the paid-sum cancel block becomes a loophole.
