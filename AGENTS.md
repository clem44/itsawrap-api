# AGENTS.md

## Project Overview

This is a Laravel API application for the It's A Wrap POS/data model, with a server-rendered admin CMS for managing the API data.

The repository root is the Laravel app. The `README.md` still refers to an `api/` subdirectory in places, but the current app files (`artisan`, `app/`, `routes/`, `database/`, `resources/`) live at the repo root.

Primary surfaces:

- Public API: guest checkout endpoints under `/api/guest/*`.
- Authenticated API: Sanctum-protected JSON endpoints under `/api/*`.
- Admin CMS: Blade/Tailwind/Alpine pages under `/admin/*`, protected by Laravel session auth plus the `admin` middleware.
- API docs: L5 Swagger annotations in `app/`, generated docs in `storage/api-docs/`, and admin entry point at `/admin/api-docs`.

## Stack

- PHP 8.2+, Laravel 12, Eloquent, Sanctum.
- PHPUnit 11 with in-memory SQLite for tests.
- Vite 7, Tailwind CSS 4, `@tailwindcss/forms`.
- Admin views are Blade templates with Alpine.js loaded from CDN in the admin layout. Vue is installed but is not the main admin UI architecture.

## Important Commands

Install/setup:

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
```

Development:

```bash
composer run dev
php artisan serve
npm run dev
```

Validation:

```bash
composer test
php artisan test
./vendor/bin/pint
npm run build
```

Use focused tests while iterating when possible, for example:

```bash
php artisan test tests/Feature/GuestOrderingApiTest.php
```

## Application Boundaries

API code lives in:

- `routes/api.php`
- `app/Http/Controllers/Api`
- `app/Http/Requests/Api`
- `app/Models`

Admin CMS code lives in:

- `routes/web.php`
- `app/Http/Controllers/Admin`
- `resources/views/admin`
- `resources/css/app.css`

Database structure lives in:

- `database/migrations`
- `database/seeders`
- `database_schema.md`

Treat the Laravel migrations and models as the working source of truth. `database_schema.md` documents the original Flutter/POS SQLite schema and is useful context, but newer Laravel migrations add fields that are not fully reflected there.

## Auth And Access Rules

The JSON API uses Sanctum bearer tokens:

- `POST /api/login` expects `username`, `password`, and `device_name`.
- Authenticated routes are grouped behind `auth:sanctum`.
- API logout deletes the current access token.

The admin CMS uses session auth:

- Admin login is `/admin/login`.
- Admin-only routes use `auth` and `admin` middleware.
- Admin access currently means `role_id === 1`.
- Non-admin web users are logged out and redirected back to the admin login form.

Keep these auth paths separate. Do not make admin CMS pages depend on bearer tokens, and do not make mobile/API workflows depend on web sessions.

## Guest Checkout Rules

Guest web checkout is intentionally separate from authenticated POS order creation.

Guest endpoints:

- `GET /api/guest/menu`
- `POST /api/guest/customers`
- `POST /api/guest/orders`

Guest behavior to preserve:

- Guest customers are tagged with `source = guest-web`.
- Guest orders must use customers whose `source` is `guest-web`.
- Guest orders use the `pending` status when available, falling back to status id `1`.
- Guest orders set `session_id = null` and must not mutate cash session totals.
- Guest orders set `source = guest-web`.
- `Idempotency-Key` is accepted via header or request body and stored as `idempotency_key`.
- Duplicate guest order processing is guarded by a cache lock.
- Guest routes are throttled in `AppServiceProvider`: menu 120/min, customer creation 20/min, order creation 10/min.

When changing guest ordering, update or add tests in `tests/Feature/GuestOrderingApiTest.php`.

## POS Order Rules

Authenticated API order creation in `App\Http\Controllers\Api\OrderController` is for POS/mobile usage:

- It attaches an open cash session for the authenticated user when one exists.
- It increments session `total_sales` and `total_service_charge`.
- It supports nested `items` and `options` payloads.
- It supports option quantities and `parent_option_value_id` for dependent options.

Do not reuse POS order creation behavior for guest checkout without deliberately preserving the different session/accounting behavior.

## Data Model Notes

Core POS/CMS data includes users, categories, items, options, option values, item options, item option values, option dependencies, taxes, customers, cash sessions, statuses, orders, order items, order item options, payments, tips, withdrawals, settings, branches, and downloads.

Notable relationships and conventions:

- Items belong to categories and can have many item options and taxes.
- Options have option values; item options connect options to specific items.
- Item option values allow per-item option pricing and stock/quantity behavior.
- Option dependencies use `parent_option_value_id` pointing to `item_option_values` and `child_option_id` pointing to `item_options`.
- Order item options use `parent_option_value_id` pointing to an `option_values` row to represent selected dependent option context.
- Branches and items are many-to-many through `branch_items`.
- Orders and tips use soft deletes.

Be careful with similarly named tables and route aliases. The API currently exposes both kebab-case and snake_case routes for some option resources, such as `item-options` and `item_options`.

## Admin CMS Conventions

The admin CMS is Blade-first:

- Use Laravel named routes from `routes/web.php`.
- Use server-side validation and redirects for normal form submits.
- Use JSON only for targeted AJAX endpoints already following that pattern, such as item option updates.
- Keep views under `resources/views/admin`.
- Extend or follow `resources/views/admin/layouts/app.blade.php`.
- Keep styling aligned with `resources/css/app.css`: DM Sans body text, Fraunces headings, and existing CSS variables (`--color-forest`, `--color-sage`, `--color-terracotta`, `--color-cream`, etc.).
- Alpine.js is available in admin pages through the layout.

When adding admin sections, update both desktop and mobile navigation in the layout if the section should be globally reachable.

## API Documentation

Controllers use PHP attributes from `OpenApi\Attributes as OA`.

When adding or changing API endpoints:

- Keep route behavior and OpenAPI attributes in sync.
- Regenerate Swagger docs when needed with `php artisan l5-swagger:generate`.
- Remember the docs configuration scans `app/` and writes JSON to `storage/api-docs/api-docs.json`.

## Testing Guidance

The PHPUnit config uses:

- `APP_ENV=testing`
- `DB_CONNECTION=sqlite`
- `DB_DATABASE=:memory:`
- `CACHE_STORE=array`
- `QUEUE_CONNECTION=sync`
- `SESSION_DRIVER=array`

Prefer feature tests for route/controller behavior and model/database assertions for workflow rules. Use `RefreshDatabase` for tests that touch persistence.

Important existing coverage:

- `tests/Feature/GuestOrderingApiTest.php` covers guest menu filtering, guest customer tagging, guest order status/session behavior, idempotency replay, customer-source validation, and throttling.

## Coding Guidelines

- Follow existing Laravel patterns before introducing new abstractions.
- Use Form Request classes for complex request validation, especially public/guest API inputs.
- Use transactions for multi-row writes that must stay consistent.
- Keep mass assignment lists (`$fillable`) and casts updated when migrations add model fields.
- Use Eloquent relationships for normal app code; use query builder/joins for explicit reporting or summary queries where the existing controllers already do so.
- Do not edit `.env` or commit environment-specific secrets.
- Do not rewrite unrelated formatting or existing user changes.
- If a change affects public API behavior, add or update a feature test.
- If a change affects admin UI behavior, check both desktop and mobile layouts.
