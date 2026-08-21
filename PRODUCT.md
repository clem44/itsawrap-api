# Product

<!-- impeccable:product-schema 1 -->

## Platform

web

## Users

- **Owner / central office (remote)** — the primary user of the admin CMS (`/admin/*`). Manages menu (categories, items, options), branches, reporting, users, and API docs from off-site, not from the shop counter.
- **On-site shop staff** — use POS/mobile order creation (`App\Http\Controllers\Api\OrderController`, Sanctum-authenticated) during service: taking orders, running cash sessions, tips, withdrawals. Inferred from the schema and controller behavior (cash session attach/increment on order creation), not directly confirmed.
- **End customers (planned)** — will place orders through a customer-facing web ordering site that calls the public `/api/guest/*` endpoints (menu, customer creation, order creation). That storefront does not exist in this repository yet; `resources/views/welcome.blade.php` is still the unmodified Laravel starter page.

## Product Purpose

A Laravel API and admin CMS that runs the day-to-day data and operations for It's A Wrap, a real, currently single-location fast-casual wrap shop. It unifies POS/mobile ordering, cash-session accounting, menu/option management, and (eventually) online guest ordering behind one data model, with the admin CMS as the owner's remote management surface.

## Positioning

Inferred from the schema, not confirmed directly: the system is purpose-built around this shop's own menu structure — nested item options with per-item pricing/stock and dependent option values (`parent_option_value_id`) — rather than a generic off-the-shelf POS. Treat this as a hypothesis, not a claim to repeat externally.

## Operating Context

- Single physical shop today; the schema (`branches`, `branch_items`) is already multi-branch-ready for future locations.
- Cash sessions track `total_sales` and `total_service_charge` per session; POS order creation attaches the authenticated user's open cash session.
- Guest web checkout is deliberately isolated from POS/cash-session accounting: guest orders set `session_id = null`, use `source = guest-web`, and never mutate cash session totals.
- Two separate auth paths: Sanctum bearer tokens for API/POS/mobile, Laravel session auth (`role_id === 1`) for the admin CMS. These are not meant to depend on each other.

## Capabilities and Constraints

- Guest endpoints are rate-limited (menu 120/min, customer creation 20/min, order creation 10/min) and idempotency-guarded.
- Admin CMS is Blade-first with server-side validation/redirects; JSON/AJAX is used only for a few targeted endpoints (e.g. item option updates).
- Undecided: whether the planned customer-facing ordering site will live in this repository or a separate project. Do not assume its stack or scope until that's decided.

## Brand Commitments

"It's A Wrap" is the real business name for this single shop. No other confirmed brand voice, personality, or asset commitments were captured in this session.

## Evidence on Hand

Real production data model (categories, items, options, branches) is the actual menu/content source — there is no separate marketing copy or asset library to draw from yet. State explicitly: no customer testimonials, case studies, press, or pricing claims are on hand; future work must not fabricate them.

## Product Principles

- Keep the two auth boundaries separate: admin CMS pages stay session-based, POS/mobile/API flows stay Sanctum-based. Don't couple one to the other.
- Guest web ordering must never touch POS cash-session accounting or reporting; it is a parallel, isolated order source.
- The schema already supports multiple branches, but only one shop is live — don't invent multi-branch UI complexity the current single owner doesn't need, and don't hard-code single-branch assumptions that would block the schema's existing growth path either.
- The admin CMS is designed for one remote owner/operator checking in and managing the business, not for a high-traffic in-store staff screen — optimize for clarity and oversight over rapid multi-user throughput.
- The customer-facing guest ordering site is a planned surface with no existing implementation or visual identity of its own — new work there starts from a deliberate direction decision (see `new-work.md`), not from assumptions carried over from the admin CMS.
