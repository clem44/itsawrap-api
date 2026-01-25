# Database Schema (SQLite)

Source of truth: `lib/services/database_service.dart` (the `_onCreate` method).
This schema is used by the Flutter app via SQLite (sqflite). The same tables and
relationships should be mirrored in the Laravel API backend.

## Overview

- Primary keys are `INTEGER PRIMARY KEY AUTOINCREMENT` unless noted.
- Timestamps are stored as `TEXT` with defaults like `CURRENT_TIMESTAMP`.
- Boolean-like flags are stored as `INTEGER` (`0/1`).
- Foreign keys are defined, but SQLite enforces them only when `PRAGMA foreign_keys = ON`.
- Default data exists for `status`, `users`, `categories`, and `settings`.

## Tables

### users
- id
- firstname (TEXT, required)
- lastname (TEXT, required)
- username (TEXT, unique, required)
- email (TEXT, unique, optional)
- password (TEXT, required, SHA-256 in current app)
- role_id (INTEGER, default 2)
- last_login (TEXT)
- created_at (TEXT, default now)
- updated_at (TEXT, default now)

### categories
- id
- name (TEXT, required)
- description (TEXT)
- icon (TEXT)
- color (TEXT)
- sort_order (INTEGER, default 0)
- created_at (TEXT, default now)
- updated_at (TEXT, default now)

### items
- id
- name (TEXT, required)
- description (TEXT)
- cost (REAL, default 0)
- category_id (INTEGER, FK -> categories.id)
- media_id (INTEGER, unused in schema, no FK)
- stock_id (INTEGER, unused in schema, no FK)
- active (INTEGER, default 1)
- image_path (TEXT)
- short_code (TEXT)
- created_at (TEXT, default now)
- updated_at (TEXT, default now)

### options
- id
- name (TEXT, required)

### option_values
- id
- option_id (INTEGER, FK -> options.id)
- name (TEXT, required)
- price (REAL, default 0)

### item_options
Links items to options, with configuration for selection rules.
- id
- item_id (INTEGER, FK -> items.id)
- option_id (INTEGER, FK -> options.id)
- required (INTEGER, default 0)
- type (TEXT, default 'single')  # e.g. 'single' or 'multiple'
- range (INTEGER, default 0)
- max (INTEGER)
- min (INTEGER)
- created_at (TEXT, default now)
- updated_at (TEXT, default now)

### item_option_values
Links item_options to option_values, with per-item pricing and stock.
- id
- item_option_id (INTEGER, FK -> item_options.id)
- option_value_id (INTEGER, FK -> option_values.id)
- price (REAL, default 0)
- in_stock (INTEGER, default 1)
- option_dependency_id (INTEGER, optional; no FK in schema)

### option_dependencies
Defines conditional options: a chosen parent option_value enables a child option.
- id
- parent_option_value_id (INTEGER, FK -> item_option_values.id)
- child_option_id (INTEGER, FK -> item_options.id)

### taxes
- id
- name (TEXT, required)
- value (REAL, default 0)
- description (TEXT)
- type (TEXT, default 'percentage')  # e.g. 'percentage' or 'fixed'

### item_taxes
Many-to-many join between items and taxes.
- id
- item_id (INTEGER, FK -> items.id)
- tax_id (INTEGER, FK -> taxes.id)

### customers
- id
- name (TEXT, required)
- firstname (TEXT)
- lastname (TEXT)
- phone (TEXT)
- email (TEXT)
- created_at (TEXT, default now)
- updated_at (TEXT, default now)

### sessions
Cash register sessions.
- id
- user_id (INTEGER, FK -> users.id)
- opening_amount (REAL, default 0)
- closing_amount (REAL)
- expected_amount (REAL)
- total_sales (REAL, default 0)
- total_tips (REAL, default 0)
- total_service_charge (REAL, default 0)
- total_withdrawals (REAL, default 0)
- is_open (INTEGER, default 1)
- opened_at (TEXT, default now)
- closed_at (TEXT)
- created_at (TEXT, default now)
- updated_at (TEXT, default now)

### status
Order status lookup.
- id
- name (TEXT, required)
- description (TEXT)

### orders
- id
- number (TEXT)
- customer_id (INTEGER, FK -> customers.id)
- status_id (INTEGER, FK -> status.id, default 1)
- subtotal (REAL, default 0)
- discount (REAL)
- discount_percent (REAL)
- service_charge (REAL, default 0)
- total (REAL, default 0)
- comments (TEXT)
- is_delivery (INTEGER, default 0)
- is_reward (INTEGER, default 0)
- session_id (INTEGER, FK -> sessions.id)
- created_at (TEXT, default now)
- updated_at (TEXT, default now)
- deleted_at (TEXT)

### order_items
- id
- order_id (INTEGER, FK -> orders.id)
- item_id (INTEGER, FK -> items.id)
- price (REAL, default 0)
- quantity (INTEGER, default 1)

### order_item_options
- id
- order_item_id (INTEGER, FK -> order_items.id)
- option_value_id (INTEGER, FK -> option_values.id)
- price (REAL, default 0)
- qty (INTEGER, nullable)

### payments
- id
- order_id (INTEGER, FK -> orders.id)
- amount (REAL, default 0)
- method (TEXT, default 'cash')
- status (TEXT, default 'pending')
- type (TEXT)
- charges (TEXT) # serialized metadata
- created_at (TEXT, default now)
- updated_at (TEXT, default now)

### tips
- id
- order_id (INTEGER, FK -> orders.id)
- amount (REAL, default 0)
- created_at (TEXT, default now)
- updated_at (TEXT, default now)
- deleted_at (TEXT)

### withdrawals
- id
- description (TEXT)
- amount (REAL, default 0)
- session_id (INTEGER, FK -> sessions.id)
- user_id (INTEGER, FK -> users.id)
- created_at (TEXT, default now)
- updated_at (TEXT, default now)

### settings
- id
- key (TEXT, unique, required)
- value (TEXT)
- created_at (TEXT, default now)
- updated_at (TEXT, default now)

### branches
- id
- name (TEXT, required)
- address (TEXT)
- phone (TEXT)
- active (INTEGER, default 1)
- created_at (TEXT, default now)
- updated_at (TEXT, default now)

### branch_items
Many-to-many join between branches and items.
- id
- branch_id (INTEGER, FK -> branches.id, ON DELETE CASCADE)
- item_id (INTEGER, FK -> items.id, ON DELETE CASCADE)
- created_at (TEXT, default now)
- UNIQUE(branch_id, item_id)

## Relationship Summary

- categories 1—* items
- options 1—* option_values
- items 1—* item_options
- item_options 1—* item_option_values
- item_option_values 1—* option_dependencies (as parent)
- item_options 1—* option_dependencies (as child)
- items *—* taxes (via item_taxes)
- customers 1—* orders
- status 1—* orders
- sessions 1—* orders
- orders 1—* order_items
- order_items 1—* order_item_options
- orders 1—* payments
- orders 1—* tips
- users 1—* sessions
- users 1—* withdrawals
- sessions 1—* withdrawals
- branches *—* items (via branch_items)

## Defaults Seeded at Install

- status: pending, active, completed, cancelled
- users: admin/admin123 (password stored as SHA-256 hash)
- categories: Wraps, Bowls, Salads, Sides, Sauces, Burgers, Merchandise, Other
- settings: service_charge, currency, currency_symbol, business_name, business_address,
  business_phone, receipt_footer

## Notes for Laravel API

- Consider using migrations to match column types and defaults exactly.
- Enforce unique constraints for `users.username`, `users.email`, and `settings.key`.
- If using MySQL/Postgres, map `TEXT` timestamps to proper `timestamp` fields.
- Decide whether to keep soft-delete fields (`orders.deleted_at`, `tips.deleted_at`) as nullable.
