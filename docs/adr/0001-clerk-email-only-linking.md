# ADR 0001: Clerk linked to Customers by verified email only

- **Status**: Accepted
- **Date**: 2026-11-15

## Context

Customers may sign in with Clerk to see their order history (`/v1/my-orders`) and manage
their contact/delivery details (`/v1/my-account`). Guest ordering stays public and
Manage-Token-based. We needed a way to bind a Clerk identity to a `customers` row.

## Decision

Clerk is used **only** as an assertion of a verified email address. No `clerk_user_id`
(or any Clerk identifier) is stored in the database. On each authenticated request the
API verifies the Bearer JWT locally against Clerk's JWKS (`iss`, `azp`, expiry, and
`email_verified` must be true) and maps the JWT's email to `customers.email`,
creating the row from the JWT's name + email if absent.

Consequences:

- Emails changed in Clerk are ignored: a changed email simply matches (or creates) a
  different Customer row; previous order history is not re-linked. Accepted risk.
- `email_verified` is mandatory — otherwise an unverified email claim would grant
  access to that email's order history.
- An authenticated order request whose payload email differs from the JWT email is
  rejected (422) rather than linked to another Customer.

## Alternatives rejected

- **Nullable unique `clerk_user_id` on `customers`, auto-provision/link on first
  verified request**: survives Clerk email changes and email-claim collisions, but adds
  a column, linking logic, and conflict handling for a single-customer-per-email
  domain where `customers.email` is already unique. Rejected for simplicity.
- **Clerk webhooks syncing users**: rejected — no local identity state to keep in sync.

## Notes

- Sanctum-based accounts (`/v1/account/*` routes and `AccountController`) were removed;
  the `laravel/sanctum` package and its migrations are retained dormant.
- Guest ordering, Order Lookup, and Manage Token behaviour are unchanged.
