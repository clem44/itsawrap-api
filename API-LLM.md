# API Reference for Developers and AI Agents

Generated for the Laravel app at the repository root. Use this as a high-signal guide to the JSON API and data model. The runtime source of truth remains `routes/api.php`, API controllers under `app/Http/Controllers/Api`, request classes under `app/Http/Requests/Api`, Eloquent models under `app/Models`, and generated OpenAPI JSON at `storage/api-docs/api-docs.json`.

## API Basics

- Base API prefix: `/api`.
- Body format: JSON unless an endpoint explicitly says otherwise.
- Authenticated API auth: Laravel Sanctum Bearer token.
- Login: `POST /api/login` returns a token. Send it as `Authorization: Bearer <token>`.
- Admin CMS routes use web sessions and are not the same auth surface as this API.
- Public guest routes are intentionally separate from staff/POS routes.
- Staff routes require `auth:sanctum` plus `staff` middleware.
- Customer self-service routes under `/api/me/*` require `auth:sanctum` plus `customer` middleware.

## Media Object Convention

Several menu/catalog models use Plank Mediable with tag `primary_image`.

Media-aware API responses expose:

```json
{
  "primary_image_url": "https://example.test/storage/media-library/file.jpg",
  "primary_media": {
    "id": 10,
    "basename": "file.jpg",
    "filename": "file",
    "extension": "jpg",
    "mime_type": "image/jpeg",
    "aggregate_type": "image",
    "size": 12345,
    "url": "https://example.test/storage/media-library/file.jpg",
    "preview_url": "https://example.test/storage/media-library/file.jpg",
    "alt": ""
  }
}
```

Write APIs that accept `media_id` sync that media as `primary_image`. Passing no `media_id` usually leaves existing media unchanged on API update; passing `media_id: null` or an empty value clears it where controller code checks for the key.

Media-aware models:

- `Category`
- `Item`
- `Option`
- `OptionValue`
- `Bundle`
- `Offer`

## Auth and User Endpoints

### `POST /api/login`

Public. Authenticates staff/customer credentials and returns a Sanctum token.

Request:

```json
{
  "username": "admin",
  "password": "password",
  "device_name": "Flutter POS App"
}
```

### `POST /api/logout`

Authenticated. Deletes the current access token.

### `GET /api/user`

Authenticated. Returns the current authenticated user.

### Staff user management

Staff-only:

- `GET /api/users`
- `POST /api/users`
- `GET /api/users/{user}`
- `PUT|PATCH /api/users/{user}`
- `DELETE /api/users/{user}`
- `GET /api/users/username-exists?username=...`
- `GET /api/users/email-exists?email=...`

User create/update fields commonly include identity fields, username/email, password, and `role_id`. See `App\Http\Controllers\Api\UserController` for exact validation.

## Public Guest Endpoints

These do not require Bearer auth.

### `GET /api/guest/menu`

Returns active storefront menu data: categories, items, options, option values, taxes, media objects, dependencies, bundles/offers as supported by the presenter. Use this for guest ordering UI.

### `GET /api/guest/bundles`

Returns currently active, orderable bundles.

### `GET /api/guest/offers`

Returns currently active customer-facing offers.

### `GET /api/guest/delivery-windows/today`

Returns delivery windows for today in restaurant timezone, with availability flags.

### `POST /api/guest/customers`

Creates a guest customer and tags it with `source = guest-web`.

Typical request:

```json
{
  "name": "Alex Carter",
  "email": "alex@example.com",
  "phone": "+12645551234"
}
```

### `POST /api/guest/register`

Registers a customer account and returns a customer token.

Request:

```json
{
  "firstname": "Alex",
  "lastname": "Carter",
  "email": "alex@example.com",
  "password": "password",
  "password_confirmation": "password",
  "device_name": "Guest Web",
  "phone": "+12645551234",
  "referral_code": "ABCD1234"
}
```

`referral_code` is optional. When supplied, referral attribution and reward progress are handled by referral services.

### `POST /api/guest/orders`

Creates a guest web order. Guest order behavior differs from POS order creation:

- Customer must be a guest customer.
- Order `source` is `guest-web`.
- Order status defaults to pending when available.
- `session_id` is `null`.
- Cash session totals are not mutated.
- Supports idempotency using `Idempotency-Key` header or request body `idempotency_key`.

Common request shape:

```json
{
  "customer_id": 1,
  "subtotal": 25.0,
  "tax": 0,
  "service_charge": 0,
  "total": 25.0,
  "is_delivery": false,
  "items": [
    {
      "item_id": 10,
      "quantity": 2,
      "price": 12.5,
      "note": "No onions",
      "participant_client_id": "person-0",
      "options": [
        {
          "option_value_id": 20,
          "price": 1.5,
          "qty": 1,
          "parent_option_value_id": null
        }
      ]
    }
  ],
  "participants": [
    {
      "client_id": "person-0",
      "name": "Alex"
    }
  ],
  "delivery": {
    "delivery_window_id": 1,
    "address_line_1": "1 Main St",
    "address_line_2": "",
    "city": "The Valley",
    "notes": "Call on arrival"
  }
}
```

### `GET /api/guest/orders/{number}`

Looks up a guest order status/detail response by public order number and valid lookup token where required by controller logic.

## Customer Self-Service Endpoints

Require customer token.

- `GET /api/me/rewards` returns the authenticated customer's purchase/referral reward summary.
- `GET /api/me/referral-code` returns the customer's referral link/code data.
- `GET /api/me/offers` returns active offers available to customers.
- `GET /api/me/bundles` returns active bundles.
- `GET /api/me/delivery-windows/today` returns customer delivery windows.
- `POST /api/me/orders` creates an order for the authenticated customer.

Customer order request is similar to guest order but identity is resolved from the token, not `customer_id`.

```json
{
  "subtotal": 25.0,
  "tax": 0,
  "service_charge": 0,
  "total": 25.0,
  "is_delivery": false,
  "items": [
    {
      "item_id": 10,
      "quantity": 1,
      "price": 12.5,
      "options": []
    }
  ]
}
```

## Staff Catalog Endpoints

Require staff token.

### Categories

- `GET /api/categories`
- `POST /api/categories`
- `GET /api/categories/{category}`
- `PUT|PATCH /api/categories/{category}`
- `DELETE /api/categories/{category}`

Create/update request:

```json
{
  "name": "Wraps",
  "description": "Menu wraps",
  "icon": "wrap-icon",
  "color": "#7c9a8a",
  "sort_order": 1
}
```

Responses include `primary_media` and nested items where loaded.

### Items

- `GET /api/items?category_id=1&active=true`
- `POST /api/items`
- `GET /api/items/{item}`
- `PUT|PATCH /api/items/{item}`
- `DELETE /api/items/{item}`
- `POST /api/items/{item}/options`

Create/update request:

```json
{
  "name": "Chicken Wrap",
  "description": "Grilled chicken wrap",
  "cost": 12.5,
  "category_id": 1,
  "active": true,
  "media_id": 10,
  "image_path": null,
  "short_code": "CHKWRAP",
  "tax_ids": [1, 2]
}
```

`media_id` attaches the selected media as item `primary_image`.

### Sync item options

`POST /api/items/{item}/options`

Replaces all item option groups for an item. This is the high-level customizer sync endpoint.

Request:

```json
{
  "options": [
    {
      "option_id": 1,
      "sort_order": 1,
      "required": true,
      "type": null,
      "range": 0,
      "max": null,
      "min": null,
      "qty": null,
      "enable_qty": false,
      "values": [
        {
          "option_value_id": 1,
          "price": 0,
          "in_stock": true,
          "qty": null,
          "option_dependency_id": null,
          "dependency": {
            "child_option_id": 2,
            "child_values": [
              {
                "option_value_id": 3,
                "price": 1.5
              }
            ]
          }
        }
      ]
    }
  ]
}
```

Rules:

- `qty` on values is only allowed when parent item option has `enable_qty = true`.
- Nested `dependency` creates dependent item options and option dependency links.
- Existing item options for the item are deleted/recreated by this endpoint.

### Options

- `GET /api/options`
- `POST /api/options`
- `GET /api/options/{option}`
- `PUT|PATCH /api/options/{option}`
- `DELETE /api/options/{option}`

Create request:

```json
{
  "name": "Protein",
  "title": "Choose protein",
  "description": "Select a protein.",
  "media_id": 10,
  "values": [
    {
      "name": "Chicken",
      "price": 3,
      "media_id": 11
    }
  ]
}
```

Notes:

- `media_id` at root attaches media to `Option`.
- `values[*].media_id` attaches media to each created `OptionValue`.
- Responses include `primary_media` for both option and nested option values.

### Option values

- `GET /api/option-values?option_id=1`
- `POST /api/option-values`
- `GET /api/option-values/{option_value}`
- `PUT|PATCH /api/option-values/{option_value}`
- `DELETE /api/option-values/{option_value}`

Create/update request:

```json
{
  "option_id": 1,
  "name": "Large",
  "price": 2,
  "media_id": 11
}
```

`media_id` attaches media to `OptionValue` as `primary_image`.

### Item options

Kebab and snake aliases exist:

- `GET /api/item-options`
- `POST /api/item-options`
- `GET /api/item-options/{item_option}`
- `PUT|PATCH /api/item-options/{item_option}`
- `DELETE /api/item-options/{item_option}`
- `GET /api/item_options`
- `POST /api/item_options`
- `GET /api/item_options/{item_option}`
- `PUT|PATCH /api/item_options/{item_option}`
- `DELETE /api/item_options/{item_option}`

Create/update fields:

```json
{
  "item_id": 1,
  "option_id": 1,
  "sort_order": 1,
  "required": false,
  "type": null,
  "range": 0,
  "max": null,
  "min": null,
  "qty": null,
  "enable_qty": false
}
```

### Item option values

Kebab and snake aliases exist:

- `GET /api/item-option-values`
- `POST /api/item-option-values`
- `GET /api/item-option-values/{item_option_value}`
- `PUT|PATCH /api/item-option-values/{item_option_value}`
- `DELETE /api/item-option-values/{item_option_value}`
- `GET /api/item_option_values`
- `POST /api/item_option_values`
- `GET /api/item_option_values/{item_option_value}`
- `PUT|PATCH /api/item_option_values/{item_option_value}`
- `DELETE /api/item_option_values/{item_option_value}`

Create/update fields:

```json
{
  "item_option_id": 1,
  "option_value_id": 1,
  "price": 0.5,
  "in_stock": true,
  "qty": null,
  "option_dependency_id": null
}
```

### Option dependencies

- `GET /api/option_dependencies?parent_option_value_id=1&child_option_id=2`
- `POST /api/option_dependencies`
- `GET /api/option_dependencies/{option_dependency}`
- `PUT|PATCH /api/option_dependencies/{option_dependency}`
- `DELETE /api/option_dependencies/{option_dependency}`

Request:

```json
{
  "parent_option_value_id": 10,
  "child_option_id": 5
}
```

`parent_option_value_id` points to an `item_option_values` row. `child_option_id` points to an `item_options` row.

### Taxes

- `GET /api/taxes`
- `POST /api/taxes`
- `GET /api/taxes/{tax}`
- `PUT|PATCH /api/taxes/{tax}`
- `DELETE /api/taxes/{tax}`

Request:

```json
{
  "name": "VAT",
  "rate": 15,
  "type": "percentage"
}
```

### Branches

- `GET /api/branches`
- `POST /api/branches`
- `GET /api/branches/{branch}`
- `PUT|PATCH /api/branches/{branch}`
- `DELETE /api/branches/{branch}`
- `POST /api/branches/{branch}/items`

Branch request:

```json
{
  "name": "Main Branch",
  "address": "1 Main St",
  "phone": "+12645551234",
  "active": true
}
```

Sync branch items:

```json
{
  "item_ids": [1, 2, 3]
}
```

### Bundles

- `GET /api/bundles`
- `GET /api/guest/bundles`
- `GET /api/me/bundles`

Returns active/orderable bundles with bundle items and option value selections. Bundle management is mostly admin CMS, not full public CRUD in `routes/api.php`.

### Offers

- `GET /api/guest/offers`
- `GET /api/me/offers`

Returns currently active customer-facing offers. Offer management is mostly admin CMS.

## Staff Orders and POS Endpoints

### Orders

- `GET /api/orders`
- `POST /api/orders`
- `GET /api/orders/{order}`
- `PUT|PATCH /api/orders/{order}`
- `DELETE /api/orders/{order}`
- `GET /api/orders/history`
- `POST /api/orders/{order}/rewards/redeem`
- `POST /api/orders/{order}/referral-rewards/redeem`

POS order creation attaches the authenticated user's open cash session when one exists and mutates session totals. Do not reuse this behavior for guest checkout.

Create request:

```json
{
  "customer_id": 1,
  "status_id": 1,
  "subtotal": 25,
  "tax": 0,
  "service_charge": 0,
  "total": 25,
  "items": [
    {
      "item_id": 1,
      "quantity": 1,
      "price": 12.5,
      "note": "No onions",
      "options": [
        {
          "option_value_id": 1,
          "price": 0.5,
          "qty": 1,
          "parent_option_value_id": null
        }
      ]
    }
  ]
}
```

Reward redemption:

```json
{
  "reward_program_id": 1,
  "item_id": 10
}
```

Referral reward redemption:

```json
{
  "referral_program_id": 1,
  "item_id": 10
}
```

### Order items

- `GET /api/order-items`
- `POST /api/order-items`
- `GET /api/order-items/{order_item}`
- `PUT|PATCH /api/order-items/{order_item}`
- `DELETE /api/order-items/{order_item}`

Create/update fields:

```json
{
  "order_id": 1,
  "item_id": 1,
  "quantity": 1,
  "price": 12.5,
  "note": "No onions",
  "options": [
    {
      "option_value_id": 1,
      "price": 0.5,
      "qty": 1,
      "parent_option_value_id": null
    }
  ]
}
```

### Payments

- `GET /api/payments`
- `POST /api/payments`
- `GET /api/payments/{payment}`
- `PUT|PATCH /api/payments/{payment}`
- `DELETE /api/payments/{payment}`

Request:

```json
{
  "order_id": 1,
  "amount": 25,
  "method": "cash",
  "reference": "optional"
}
```

### Tips

- `GET /api/tips`
- `POST /api/tips`
- `GET /api/tips/{tip}`
- `PUT|PATCH /api/tips/{tip}`
- `DELETE /api/tips/{tip}`

Request:

```json
{
  "order_id": 1,
  "amount": 5
}
```

### Cash sessions

- `GET /api/sessions`
- `POST /api/sessions`
- `GET /api/sessions/current`
- `GET /api/sessions/{session}`
- `POST /api/sessions/{cashSession}/close`
- `PATCH /api/sessions/{cashSession}/totals`

Open request:

```json
{
  "opening_amount": 100
}
```

Close request:

```json
{
  "closing_amount": 500
}
```

Totals patch request:

```json
{
  "total_sales": 250,
  "total_service_charge": 25
}
```

### Withdrawals

- `GET /api/withdrawals`
- `POST /api/withdrawals`
- `GET /api/withdrawals/{withdrawal}`
- `DELETE /api/withdrawals/{withdrawal}`

Request:

```json
{
  "amount": 50,
  "reason": "Cash drop",
  "session_id": 1
}
```

## Settings, Statuses, Rewards, Notifications

### Settings

- `GET /api/settings`
- `GET /api/settings/{key}`
- `PUT /api/settings/{key}`
- `POST /api/settings/bulk`

Single setting update:

```json
{
  "value": "new value"
}
```

Bulk update:

```json
{
  "settings": {
    "restaurant_name": "It's A Wrap",
    "service_charge": "10"
  }
}
```

### Statuses

- `GET /api/statuses`
- `POST /api/statuses`
- `GET /api/statuses/{status}`
- `PUT|PATCH /api/statuses/{status}`
- `DELETE /api/statuses/{status}`

Request:

```json
{
  "name": "pending",
  "color": "#7c9a8a",
  "sort_order": 1
}
```

### Rewards

- `GET /api/rewards/programs`
- `GET /api/customers/{customer}/rewards`
- `GET /api/me/rewards`
- `POST /api/orders/{order}/rewards/redeem`
- `POST /api/orders/{order}/referral-rewards/redeem`

Reward program records define earning category, reward category, thresholds, active dates, and quantities.

### Referral code

- `GET /api/me/referral-code`

Returns authenticated customer's referral code/link.

### Push subscriptions

- `POST /api/push-subscriptions`
- `DELETE /api/push-subscriptions/{pushSubscription}`

Request:

```json
{
  "token": "fcm-or-apns-token",
  "platform": "ios",
  "app_context": "customer",
  "device_name": "Alex's iPhone",
  "personal_access_token_id": null
}
```

## Delivery Endpoints

- `GET /api/guest/delivery-windows/today`
- `GET /api/me/delivery-windows/today`

Delivery window records can be recurring by weekday or specific date. Availability can be affected by capacity and time.

Delivery order payloads use a nested `delivery` object with selected `delivery_window_id`, address fields, and notes.

## Core Model Structures

This section describes practical API-facing structures, not every database column.

### `User`

Fields:

- `id`
- `firstname`
- `lastname`
- `username`
- `email`
- `password`
- `role_id`
- referral/customer linkage fields as present in migrations

Relationships:

- `role`
- `sessions`
- `withdrawals`
- `pushSubscriptions`
- `deliveryWindows`
- `assignedDeliveries`
- `customer`
- referral relationships

### `Role`

Fields:

- `id`
- `name`
- `description`

Relationship: `users`.

Admin access currently means `role_id === 1`; staff/customer middleware uses app-specific role checks.

### `Category`

Fields:

- `id`
- `name`
- `description`
- `icon`
- `color`
- `sort_order`
- `media_id` legacy column may exist, but Plank Mediable `primary_image` is preferred.

Relationships:

- `items`
- reward/referral program category relationships
- media via Plank Mediable

API response includes `primary_image_url` and `primary_media`.

### `Item`

Fields:

- `id`
- `name`
- `description`
- `cost`
- `category_id`
- `media_id` legacy column may exist
- `stock_id`
- `active`
- `image_path`
- `short_code`

Relationships:

- `category`
- `itemOptions`
- `taxes`
- `branches`
- `orderItems`
- media via Plank Mediable

API response includes `primary_image_url`, `primary_media`, category, taxes, and configured options depending on endpoint.

### `Option`

Fields:

- `id`
- `name`
- `title`
- `description`

Relationships:

- `optionValues`
- `itemOptions`
- media via Plank Mediable

API response includes `primary_image_url`, `primary_media`, and nested option values with their own media.

### `OptionValue`

Fields:

- `id`
- `option_id`
- `name`
- `price`

Relationships:

- `option`
- `optionDependencies`
- `itemOptionValues`
- `orderItemOptions`
- media via Plank Mediable

API response includes `primary_image_url` and `primary_media`.

### `ItemOption`

Fields:

- `id`
- `item_id`
- `option_id`
- `sort_order`
- `required`
- `type`
- `range`
- `max`
- `min`
- `qty`
- `enable_qty`

Relationships:

- `item`
- `option`
- `itemOptionValues`
- `childDependencies`

An `ItemOption` is the option group as configured for one item.

### `ItemOptionValue`

Fields:

- `id`
- `item_option_id`
- `option_value_id`
- `price`
- `in_stock`
- `qty`
- `option_dependency_id`

Relationships:

- `itemOption`
- `optionValue`
- `parentDependencies`
- `optionDependency`

This is the per-item availability/price row for an `OptionValue`.

### `OptionDependency`

Fields:

- `id`
- `parent_option_value_id`
- `child_option_id`

Relationships:

- `parentOptionValue` points to `ItemOptionValue`
- `childOption` points to `ItemOption`

### `Order`

Fields:

- `id`
- `number`
- `customer_id`
- `status_id`
- `session_id`
- `subtotal`
- `tax`
- `service_charge`
- `total`
- `source`
- `idempotency_key`
- soft delete timestamps

Relationships:

- `customer`
- `status`
- `session`
- `orderItems`
- `participants`
- `rewardLedgerEntries`
- `delivery`
- `payments`
- `tips`

Guest orders and POS orders follow different accounting rules.

### `OrderItem`

Fields:

- `id`
- `order_id`
- `item_id`
- `participant_id`
- `quantity`
- `price`
- `note`
- reward/referral redemption fields

Relationships:

- `order`
- `participant`
- `item`
- `orderItemOptions`
- reward/referral ledger references

Helpers:

- `lineTotal()`
- `undiscountedLineTotal()`

### `OrderItemOption`

Fields:

- `id`
- `order_item_id`
- `option_value_id`
- `parent_option_value_id`
- `price`
- `qty`

Relationships:

- `orderItem`
- `optionValue`
- `parentOptionValue`

### `OrderParticipant`

Fields:

- `id`
- `order_id`
- `client_id`
- `name`

Relationships:

- `order`
- `orderItems`

### `Customer`

Fields:

- `id`
- `name`
- `email`
- `phone`
- `source`
- `user_id`

Relationships:

- `orders`
- `rewardAccounts`
- `rewardLedgerEntries`
- `user`

Attribute:

- `display_name`

### `CashSession`

Fields:

- `id`
- `user_id`
- `opening_amount`
- `closing_amount`
- `total_sales`
- `total_service_charge`
- opened/closed timestamps

Relationships:

- `user`
- `orders`
- `withdrawals`

### `Payment`

Fields:

- `id`
- `order_id`
- `amount`
- `method`
- `reference`

Relationship: `order`.

### `Tip`

Fields:

- `id`
- `order_id`
- `amount`

Relationship: `order`.

### `Withdrawal`

Fields:

- `id`
- `session_id`
- `user_id`
- `amount`
- `reason`

Relationships:

- `session`
- `user`

### `Status`

Fields:

- `id`
- `name`
- `color`
- `sort_order`

Relationship: `orders`.

### `Tax`

Fields:

- `id`
- `name`
- `rate`
- `type`

Relationship: `items`.

### `Branch`

Fields:

- `id`
- `name`
- `address`
- `phone`
- `active`

Relationships:

- `items`
- `deliveryWindows`

### `DeliveryWindow`

Fields:

- `id`
- `branch_id`
- `name`
- `date`
- `weekday`
- `start_time`
- `end_time`
- `capacity`
- `active`

Relationships:

- `branch`
- `drivers`
- `deliveries`

### `Delivery`

Fields:

- `id`
- `order_id`
- `delivery_window_id`
- `assigned_driver_id`
- address snapshot fields
- status/timestamps

Relationships:

- `order`
- `deliveryWindow`
- `assignedDriver`

### `Bundle`

Fields:

- `id`
- `name`
- `description`
- active/date fields
- price/availability fields
- creator fields

Relationships:

- `bundleItems`
- `offers`
- `creator`
- media via Plank Mediable

### `BundleItem`

Fields:

- `id`
- `bundle_id`
- `item_id`
- `quantity`
- `sort_order`
- `price_override`
- `label_override`

Relationships:

- `bundle`
- `item`
- `optionValues`

### `BundleItemOptionValue`

Fields:

- `id`
- `bundle_item_id`
- `item_option_id`
- `option_value_id`
- `parent_option_value_id`
- `quantity`
- `price_override`

Relationships:

- `bundleItem`
- `itemOption`
- `optionValue`
- `parentOptionValue`

### `Offer`

Fields:

- `id`
- `name`
- `description`
- qualifying/reward category and item IDs
- bundle ID
- discount/reward configuration fields
- active/date fields
- creator fields

Relationships:

- `qualifyingCategory`
- `qualifyingItem`
- `rewardCategory`
- `rewardItem`
- `bundle`
- `creator`
- media via Plank Mediable

### Reward and Referral Models

Purchase reward models:

- `RewardProgram`
- `CustomerRewardAccount`
- `RewardLedgerEntry`

Referral models:

- `ReferralProgram`
- `UserReferralCode`
- `UserReferral`
- `UserReferralRewardAccount`
- `UserReferralLedgerEntry`

These models track earning thresholds, reward balances, ledger entries, reversals, and redemptions. Prefer service classes under `app/Services/Rewards` for mutations instead of writing ledger rows directly.

### `PushSubscription`

Fields:

- `id`
- `user_id`
- `personal_access_token_id`
- `token`
- `platform`
- `app_context`
- `device_name`
- `revoked_at`

Relationships:

- `user`
- `personalAccessToken`

Scopes:

- `active`
- `forPosOrderConfirmation`

### `Setting`

Fields:

- `id`
- `key`
- `value`
- metadata fields as defined by migration/model

## Agent Guidance

- Prefer `storage/api-docs/api-docs.json` for exact OpenAPI request/response schemas.
- Prefer controller validation rules for exact required/optional request fields.
- Preserve guest/POS separation. Guest orders must not mutate cash sessions.
- For media, prefer `media_id` plus Plank Mediable tag `primary_image`; do not rely on legacy `media_id` columns where Plank relations are implemented.
- For option customizers, distinguish:
  - `options`: reusable option groups.
  - `option_values`: reusable base values.
  - `item_options`: an option group attached to one item.
  - `item_option_values`: one item-specific value with item-specific price/stock/qty.
  - `option_dependencies`: dependency links between item-specific values/groups.
- Use transactions for multi-row order, bundle, reward, and option sync mutations.
- Keep Sanctum API auth separate from web/admin session auth.
