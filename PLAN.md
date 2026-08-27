# Rewards Feature Plan

## Goal

Implement a customer rewards feature where customers earn progress toward a free category item.

Initial business rule:

- Customers earn 1 progress point for each paid purchased item in the configured earn category.
- The first program should be: buy 6 paid wraps, get 1 free wrap.
- 6 wraps in one completed order should immediately earn 1 free wrap.
- 13 paid wraps should earn 2 free wraps and carry 1 progress point forward.
- Free reward items do not count toward future reward progress.
- If a qualifying order is refunded, deleted, or cancelled, the reward progress and rewards earned from that order must be reversed.
- Rewards apply to any item in the configured reward category.
- Rewards are for real `Customer` records. Guest checkout users must create or use a customer account before earning or redeeming rewards.

## Design Direction

Build rewards as its own module with a small interface and a database-backed ledger.

Do not store the core rule only in `settings`. The existing `settings` table is useful for global flags, but reward rules need relational data, history, validation, and auditability. Administrator changes should not make old reward activity impossible to explain.

Recommended `settings` use:

- `rewards_enabled`
- optional `rewards_guest_signup_required`

The reward rule itself should live in `reward_programs`.

## Database Schema

### `reward_programs`

Stores administrator-configurable reward rules.

```text
id
name
is_active boolean default true
earn_category_id foreign key categories.id
qualifying_item_quantity_required integer default 6
reward_category_id foreign key categories.id
reward_quantity integer default 1
starts_at nullable timestamp
ends_at nullable timestamp
created_by_user_id nullable foreign key users.id
timestamps
```

Notes:

- `earn_category_id` is the category that earns progress, for example Wraps.
- `reward_category_id` is the category eligible for redemption, for example Wraps.
- Keep `qualifying_item_quantity_required` explicit so future rules can support categories other than wraps.
- Prefer deactivating old programs and creating new ones when rule meaning changes materially. Simple admin edits are acceptable before launch or for low-risk changes.

### `customer_reward_accounts`

Stores current customer balances per reward program.

```text
id
customer_id foreign key customers.id
reward_program_id foreign key reward_programs.id
progress_quantity integer default 0
rewards_available integer default 0
lifetime_qualifying_quantity integer default 0
lifetime_rewards_earned integer default 0
lifetime_rewards_redeemed integer default 0
timestamps

unique(customer_id, reward_program_id)
```

Notes:

- `progress_quantity` is the carry-forward progress below the next reward threshold.
- `rewards_available` is the number of free reward items currently redeemable.
- Lifetime fields are denormalized counters for fast display and reporting.

### `reward_ledger_entries`

Stores the audit trail for earns, redemptions, reversals, and manual adjustments.

```text
id
customer_id foreign key customers.id
reward_program_id foreign key reward_programs.id
order_id nullable foreign key orders.id
order_item_id nullable foreign key order_items.id
type string
progress_delta integer default 0
rewards_delta integer default 0
reason nullable string
metadata nullable json
created_by_user_id nullable foreign key users.id
timestamps
```

Expected `type` values:

- `earned_progress`
- `reward_earned`
- `redeemed`
- `reversed`
- `adjusted`
- `expired`

Expected `reason` values:

- `order_completed`
- `order_cancelled`
- `order_deleted`
- `payment_refunded`
- `admin_adjustment`
- `reward_redemption`

Suggested indexes:

```text
index(customer_id, reward_program_id)
index(order_id)
index(order_item_id)
index(type)
```

Use the ledger as the source of truth for explaining changes. Use `customer_reward_accounts` as the current balance projection.

### `order_items` Additions

Track reward redemption at line-item level.

```text
is_reward_item boolean default false
reward_program_id nullable foreign key reward_programs.id
reward_ledger_entry_id nullable foreign key reward_ledger_entries.id
reward_discount_amount nullable decimal(10, 2)
```

Reason:

- An order can contain paid items and free reward items at the same time.
- Existing `orders.is_reward` is too coarse for mixed orders.
- Reward items must be excluded from future earning calculations.

### `orders` Additions

Keep existing `orders.is_reward` for backwards compatibility, but do not rely on it as the primary reward marker.

Optional additions:

```text
reward_summary nullable json
```

This is optional and should only be added if receipts or order history need a frozen display summary.

## Reward Rules

### Earning

When an order becomes completed:

1. Check `settings.rewards_enabled`.
2. Confirm the order has a `customer_id`.
3. Ignore anonymous guest orders.
4. For each active reward program, sum paid order item quantities where:
   - the order item is not a reward item;
   - the item belongs to `reward_programs.earn_category_id`;
   - the order has not already generated reward ledger entries for that program.
5. Add the quantity to the customer's account progress.
6. Convert progress into rewards:
   - `new_rewards = floor((current_progress + earned_quantity) / threshold)`
   - `progress_quantity = (current_progress + earned_quantity) % threshold`
   - `rewards_available += new_rewards`
7. Write ledger entries that link back to the order and, when useful, the order item.

### Redemption

When a customer redeems a reward:

1. Check `settings.rewards_enabled`.
2. Confirm the customer has `rewards_available > 0`.
3. Confirm the selected item belongs to the reward program's `reward_category_id`.
4. Create or update the order item as:
   - `is_reward_item = true`
   - `price = 0` or full price with a matching discount, depending on receipt requirements
   - `reward_program_id = reward_programs.id`
   - `reward_discount_amount = normal item price`
5. Decrement `rewards_available`.
6. Increment `lifetime_rewards_redeemed`.
7. Write a `redeemed` ledger entry.

Use line-item reward metadata rather than marking the whole order as a reward order.

### Reversal

When a qualifying order is cancelled, deleted, or refunded:

1. Find reward ledger entries created from that order.
2. If those entries have already been reversed, do nothing.
3. Write reversal ledger entries rather than deleting the original entries.
4. Recompute or adjust the customer's account balance.
5. If reversing an earned reward would make the account negative because the customer already redeemed it, allow the account to represent the debt explicitly or block the refund workflow until an admin confirms the adjustment.

Recommended first version:

- Prevent negative `rewards_available`.
- Allow `progress_quantity` to decrease only to zero.
- Add an admin-visible warning if a reversal cannot be fully applied because the reward was already redeemed.

## Backend Module

Create a rewards module with a small interface, likely under `app/Services/Rewards`.

Suggested public interface:

```php
RewardService::recordOrderCompleted(Order $order): void
RewardService::reverseOrder(Order $order, string $reason, ?User $actor = null): void
RewardService::redeemReward(Order $order, OrderItem $orderItem, RewardProgram $program, ?User $actor = null): void
RewardService::summaryForCustomer(Customer $customer): RewardSummary
```

Responsibilities hidden inside the implementation:

- Loading active programs.
- Filtering qualifying order items.
- Preventing duplicate earning for the same order/program.
- Updating `customer_reward_accounts`.
- Writing ledger entries.
- Handling reversals.
- Enforcing redemption eligibility.

Keep order controllers thin. They should call the rewards module when order status/payment state changes instead of duplicating reward calculations.

## Order Lifecycle Integration

### POS API

Current POS order creation lives in `App\Http\Controllers\Api\OrderController`.

Integration points:

- Do not award rewards immediately on order creation unless the order is created directly in a completed state.
- Award rewards when the order reaches the completed/paid state.
- Exclude reward line items from earn calculations.
- Include reward state in order responses where the POS needs to show applied rewards.

### Guest API

Current guest order creation lives in `App\Http\Controllers\Api\GuestOrderController`.

Rules:

- Guest orders without a real customer account do not earn rewards.
- Guest customers must create or use a customer account before earning or redeeming.
- Guest order creation should still preserve the existing guest checkout rules:
  - `source = guest-web`
  - `session_id = null`
  - no cash session total mutation
  - idempotency behavior remains unchanged

### Payments And Refunds

Current payments allow `method = reward`.

Integration points:

- Decide whether reward redemption is represented as:
  - an `order_items` discount with normal payment methods for the remaining total; or
  - a zero-amount or reward-method `payments` record.
- If a payment is refunded and the order becomes refunded/cancelled, reverse reward earning for paid items.
- If a reward item is refunded/cancelled, restore the redeemed reward if appropriate.

Recommended first version:

- Represent the free wrap on `order_items`.
- Keep payment method `reward` available but do not make it the main source of reward accounting.

## Admin CMS

Add an admin rewards section under `/admin/rewards`.

Capabilities:

- List reward programs.
- Create/edit reward programs.
- Activate/deactivate a program.
- Choose earn category from `categories`.
- Choose reward category from `categories`.
- Set required paid item quantity, initially 6.
- View customer reward balance.
- View reward ledger entries for a customer.
- Add manual adjustment entries with required admin note.

Navigation:

- Add Rewards to desktop admin navigation.
- Add Rewards to mobile admin navigation.

Validation:

- Required quantity must be at least 1.
- Reward quantity must be at least 1.
- Categories must exist.
- Only one active program for the same earn/reward category pair unless explicitly supporting overlapping programs.

## API Surface

Authenticated API endpoints for POS/mobile/customer web app:

```text
GET /api/rewards/programs
GET /api/customers/{customer}/rewards
POST /api/orders/{order}/rewards/redeem
```

Potential admin/API endpoints:

```text
GET /api/reward-programs
POST /api/reward-programs
PUT /api/reward-programs/{rewardProgram}
PATCH /api/reward-programs/{rewardProgram}/activate
PATCH /api/reward-programs/{rewardProgram}/deactivate
GET /api/customers/{customer}/reward-ledger
POST /api/customers/{customer}/reward-adjustments
```

Keep OpenAPI annotations in sync if these endpoints are added.

## Customer Web App

The customer ordering app is at:

```text
C:\Users\clemg\Herd\itsawrapweb
```

Expected integration needs:

- Show customer's current progress, for example `4 / 6 wraps`.
- Show available rewards.
- Let eligible customers apply a free item from the reward category.
- Require customer account creation/login before rewards can be earned or redeemed.
- Prevent reward redemption for anonymous guest checkout.
- Display clear unavailable states when rewards are disabled or no reward is available.

The Laravel API should expose enough reward summary data that the web app does not need to recalculate eligibility.

## Testing Plan

Add focused feature tests before or alongside implementation.

Backend test cases:

- Paid completed order with 1 wrap adds 1 progress point.
- Paid completed order with 6 wraps earns 1 reward and leaves 0 progress.
- Paid completed order with 13 wraps earns 2 rewards and leaves 1 progress.
- Free reward wrap does not earn progress.
- Non-wrap item does not earn progress for the wrap program.
- Mixed order with paid wraps and reward wrap only counts paid wraps.
- Duplicate order completion processing does not duplicate reward entries.
- Cancelled order reverses earned progress.
- Deleted order reverses earned progress.
- Refunded order reverses earned progress.
- Reward redemption decrements available rewards.
- Redemption rejects items outside the reward category.
- Guest order without an eligible customer account does not earn rewards.
- Disabled rewards setting prevents earning and redemption.

Admin tests:

- Admin can create a reward program.
- Admin can edit reward conditions.
- Admin can deactivate a reward program.
- Admin can view customer reward balance and ledger.
- Admin adjustment requires a note.

Regression tests:

- Existing guest order tests still pass.
- POS order session total behavior remains unchanged.

## Implementation Phases

### Phase 1: Data Model

- Add migrations for reward programs, customer reward accounts, reward ledger entries, and order item reward metadata.
- Add Eloquent models and relationships.
- Seed the initial Wraps reward program if the Wraps category exists.

### Phase 2: Rewards Module

- Implement `RewardService`.
- Add reward summary value object or array transformer.
- Make earning, redemption, and reversal transactional.
- Add idempotency checks around order/program ledger creation.

### Phase 3: Order Integration

- Call reward earning when an order reaches completed status.
- Call reversal logic when an order is cancelled, deleted, or refunded.
- Add redemption support to order creation/update flow.
- Preserve guest checkout and POS cash session behavior.

### Phase 4: Admin CMS

- Add reward program management pages.
- Add customer reward balance and ledger views.
- Add manual adjustment flow.
- Update desktop and mobile navigation.

### Phase 5: API And Docs

- Add reward summary and redemption endpoints.
- Add OpenAPI annotations.
- Regenerate Swagger docs with `php artisan l5-swagger:generate`.

### Phase 6: Customer Web App

- Integrate reward summary display in `C:\Users\clemg\Herd\itsawrapweb`.
- Add redemption UI for eligible customers.
- Ensure anonymous guest checkout cannot earn or redeem.

## Open Questions

- Which order status name/id represents completed for reward earning?
- Is refund represented by payment status, order status, soft delete, or a combination?
- Should reversing an already-redeemed reward create a negative balance, block the reversal, or require an admin adjustment?
- Should reward programs be versioned by creating a new row on every condition change, or edited in place until launch?
- Should existing historical orders be backfilled into reward progress when the feature launches?
- Should a customer be identified only by `customers.id`, or should phone/email matching merge guest-created customers?

# Firebase Cloud Messaging Plan

## Goal

Send Firebase Cloud Messaging push notifications to logged-in POS devices when a new web order is created and needs staff confirmation.

Notification recipients:

- Admin users: `role_id = 1`
- Staff users: `role_id = 2`

Excluded recipients:

- Customer users: `role_id = 3`
- Admin CMS browser sessions, unless a future requirement explicitly includes them
- Customer web app devices

Initial trigger:

- A new order created by the customer web app or guest web ordering flow.
- Existing guest web orders use `source = guest-web`.

Do not notify for:

- POS-created staff orders
- Admin-created orders
- Internal updates to existing orders unless a separate notification type is added later

## Important Distinction

Sanctum does not generate Firebase Cloud Messaging tokens.

Sanctum handles API authentication:

- The POS app logs in with `POST /api/login`.
- Laravel returns a Sanctum bearer token.
- The bearer token tells the API which user/device is making a request.

Firebase handles push device registration:

- The iOS/Android POS app initializes Firebase Messaging.
- The Firebase SDK returns an FCM registration token for that app install/device.
- The POS app sends that token to Laravel using the Sanctum bearer token.

Laravel handles storage and delivery:

- Store FCM tokens against authenticated users.
- Decide which users/devices should receive a notification.
- Send pushes through Firebase.
- Revoke invalid or expired tokens.

This means the iOS/Android POS app must be updated to collect and submit FCM tokens. The API cannot generate device FCM tokens on its own.

## POS App Token Flow

1. POS app logs in normally:

```text
POST /api/login
```

2. API returns a Sanctum token.

3. POS app requests push notification permission from the operating system.

4. POS app initializes Firebase Messaging and gets an FCM registration token.

5. POS app registers the FCM token with Laravel:

```text
POST /api/push-subscriptions
Authorization: Bearer <sanctum-token>
Content-Type: application/json
```

```json
{
  "token": "firebase-fcm-registration-token",
  "platform": "ios",
  "app_context": "pos",
  "device_name": "Kitchen iPad"
}
```

6. Laravel stores the token against the authenticated user.

7. When Firebase rotates the token, the POS app sends the new token to the same endpoint.

8. On logout, the POS app should call a revoke endpoint for the current token if possible.

## Database Schema

### `push_subscriptions`

Stores FCM registration tokens for app installs/devices.

```text
id
user_id foreign key users.id
provider string default firebase
token text
token_hash string unique
platform string              // ios, android, web
app_context string           // pos, admin, customer
device_name nullable string
personal_access_token_id nullable unsigned bigint
last_seen_at nullable timestamp
revoked_at nullable timestamp
timestamps
```

Suggested indexes:

```text
unique(token_hash)
index(user_id)
index(app_context, platform, revoked_at)
index(last_seen_at)
```

Notes:

- Store the raw token because Firebase needs it for delivery.
- Store `token_hash` for lookup/upsert without indexing a long text token.
- `personal_access_token_id` can link a push token to the current Sanctum device session when available, but the push token should not live inside Sanctum.
- `revoked_at` preserves historical records while excluding stale tokens from delivery.

### Optional `push_notification_deliveries`

Add this table if delivery audit/history matters.

```text
id
user_id nullable foreign key users.id
push_subscription_id nullable foreign key push_subscriptions.id
order_id nullable foreign key orders.id
type string
title string
body text nullable
data json nullable
status string              // pending, sent, failed, invalid_token
provider_message_id nullable string
error nullable text
sent_at nullable timestamp
timestamps
```

Recommended first version:

- Skip this table unless the business needs delivery history in the admin UI.
- Use structured logs for send failures.

## Configuration

Use the Firebase Admin SDK for PHP/Laravel, preferably `kreait/laravel-firebase`.

Required server-side configuration:

```text
FIREBASE_CREDENTIALS=
FIREBASE_PROJECT_ID=
```

Notes:

- `FIREBASE_CREDENTIALS` should point to a service account JSON file or equivalent secure credential configuration.
- Do not commit Firebase service account JSON to the repository.
- Server credentials are private and belong only on the Laravel API server.

The POS app will also need its normal Firebase client configuration in iOS/Android project files.

## Backend Module

Create a POS push notification module with a small interface:

```php
PosPushNotifier::webOrderCreated(Order $order): void
```

Responsibilities hidden inside the implementation:

- Determine if the order source should trigger a POS notification.
- Find active POS push subscriptions.
- Target users with `role_id` 1 or 2 only.
- Exclude customer users with `role_id` 3.
- Dispatch queued Firebase send jobs.
- Mark invalid tokens as revoked.
- Log Firebase failures without failing order creation.

Keep controllers thin. Order creation should call one notification method after the order transaction succeeds.

## API Surface

Authenticated endpoints:

```text
POST /api/push-subscriptions
DELETE /api/push-subscriptions/{pushSubscription}
```

Optional convenience endpoint:

```text
DELETE /api/push-subscriptions/current-token
```

### Register Or Refresh Subscription

```text
POST /api/push-subscriptions
```

Payload:

```json
{
  "token": "firebase-fcm-registration-token",
  "platform": "ios",
  "app_context": "pos",
  "device_name": "Kitchen iPad"
}
```

Validation:

- Authenticated via Sanctum.
- `token` is required.
- `platform` is one of `ios`, `android`, `web`.
- `app_context` is one of `pos`, `admin`, `customer`.
- For the first version, only `app_context = pos` with `platform in [ios, android]` should be used for order-confirmation notifications.

Behavior:

- Hash token with SHA-256.
- Upsert by `token_hash`.
- Set `user_id` to the authenticated user.
- Set `last_seen_at = now()`.
- Clear `revoked_at` if the token was previously revoked and has been seen again.

### Revoke Subscription

```text
DELETE /api/push-subscriptions/{pushSubscription}
```

Behavior:

- Only allow users to revoke their own subscriptions, unless the requester is admin.
- Set `revoked_at = now()`.
- Do not hard-delete by default.

## Notification Payload

When a web order is created:

```json
{
  "notification": {
    "title": "New web order",
    "body": "Order #123 is waiting for confirmation"
  },
  "data": {
    "type": "web_order_created",
    "order_id": "123",
    "order_number": "123",
    "source": "guest-web"
  }
}
```

Notes:

- FCM data values should be strings.
- The POS app should use `data.order_id` to open the order confirmation screen.
- The message should be sent asynchronously so Firebase latency or failure does not delay order creation.

## Order Lifecycle Integration

### Guest Web Orders

Current code path:

```text
App\Http\Controllers\Api\GuestOrderController::store
```

After the order transaction succeeds and the order is available:

```php
$posPushNotifier->webOrderCreated($order);
```

This should happen after persistence succeeds. If push sending fails, the order should still be created.

### Authenticated Customer Web Orders

If the customer web app uses an authenticated customer order endpoint, add the same notifier call there.

Rule:

- Notify only when the order is from the customer web app, not when it is created by the POS app.

### POS Orders

Current code path:

```text
App\Http\Controllers\Api\OrderController::store
```

Do not trigger web-order POS notifications from normal POS order creation.

## Queueing

Use Laravel jobs for delivery:

```php
SendFirebasePushNotification::dispatch(...)
```

Queue behavior:

- Order creation dispatches jobs quickly and returns.
- Firebase send runs outside the request lifecycle.
- Failed sends are retried according to queue configuration.
- Invalid/unregistered tokens are marked revoked.

## Testing Plan

Backend tests:

- Staff user can register an iOS POS push subscription.
- Admin user can register an Android POS push subscription.
- Customer user can register a token, but customer tokens are not targeted for POS order confirmation notifications.
- Registering the same FCM token updates the existing subscription instead of creating duplicates.
- Revoking a subscription sets `revoked_at`.
- New guest web order dispatches POS push notification jobs.
- POS-created order does not dispatch web-order notification jobs.
- Notification targeting includes `role_id` 1 and 2.
- Notification targeting excludes `role_id` 3.
- Notification targeting excludes `app_context != pos`.
- Notification targeting excludes revoked subscriptions.
- Firebase sender revokes invalid tokens.
- Firebase failure does not prevent order creation.

Docs tests/checks:

- Add OpenAPI annotations for push subscription endpoints.
- Regenerate Swagger docs with `php artisan l5-swagger:generate`.
- Verify `storage/api-docs/api-docs.json` includes the push subscription paths.

## Implementation Phases

### Phase 1: Firebase Package And Config

- Install `kreait/laravel-firebase`.
- Publish or add Firebase config.
- Add env examples for Firebase credentials/project id.
- Ensure service account credentials are excluded from git.

### Phase 2: Push Subscription Data Model

- Add `push_subscriptions` migration.
- Add `PushSubscription` model.
- Add `User::pushSubscriptions()` relationship.

### Phase 3: Subscription API

- Add controller and request validation.
- Add authenticated routes.
- Add OpenAPI annotations.
- Add tests for register/update/revoke.

### Phase 4: Notification Module And Job

- Add `PosPushNotifier`.
- Add Firebase sender job.
- Add an adapter around Firebase messaging so tests can fake delivery.
- Add token revocation handling for invalid Firebase responses.

### Phase 5: Web Order Trigger

- Trigger notifications after guest web order creation.
- Trigger notifications after authenticated customer web order creation if that endpoint exists.
- Confirm POS-created orders do not notify.

### Phase 6: POS App Changes

- Add Firebase Messaging setup in iOS and Android POS apps.
- Request notification permission.
- Get the FCM registration token.
- Register the token with `POST /api/push-subscriptions` after login.
- Re-register when Firebase rotates the token.
- Handle notification taps using `data.order_id`.

## Open Questions

- Does the POS app already distinguish iOS and Android in its login payload or device metadata?
- Should all logged-in staff/admin POS devices receive every web order, or should this later be branch-specific?
- Should notifications be sent for guest web orders only, authenticated customer web orders only, or both?
- Should admin CMS browser sessions receive these notifications later, or should this stay POS-only?
- Should failed notification deliveries be persisted in `push_notification_deliveries` or only logged?

# Delivery Feature Plan

## Goal

Implement a proper delivery scheduling feature for the restaurant ordering app.

Customer-facing behavior:

- When the customer chooses delivery, the client app shows today's delivery windows.
- Delivery windows that have passed their start time are still returned, but with `is_available = false` so the client can grey them out.
- Full windows are also returned with `is_available = false`.
- Customers select one available delivery window before placing a delivery order.
- Delivery orders include address, latitude, longitude, and optional delivery instructions.

Administrator behavior:

- Administrators can create and update delivery windows in the admin dashboard.
- Administrators can create weekly recurring windows by day of week.
- Administrators can create specific-date windows for one calendar date.
- If specific-date windows exist for today, they replace the recurring windows for that weekday.
- Delivery windows should not be hidden from the customer just because no driver is assigned.
- Administrators can assign one or more drivers to delivery windows.

## Design Direction

Build delivery as its own module with a small interface around resolving availability and reserving a selected window.

The existing `orders.is_delivery` flag should remain for backwards compatibility, reporting, and existing app behavior. Delivery-specific data should live in a new `deliveries` table instead of expanding `orders` with every fulfillment detail.

The customer should always reserve a concrete delivery occurrence for today's date, even when that occurrence came from a weekly recurring window. Existing delivery orders must keep a snapshot of the chosen window time so later admin edits do not rewrite what the customer selected.

## Role Model

Add a `roles` table to make the existing `users.role_id` column explicit.

Seed fixed roles:

```text
1 admin
2 staff
3 customer
4 driver
```

Keep the existing numeric IDs because the current middleware, tests, and seeded users already depend on them.

Recommended schema:

```text
id unsigned tiny integer primary key
code string unique
name string
description nullable string
timestamps
```

Update `users.role_id` to reference `roles.id`.

Driver rule:

- A driver is a `User` with `role_id = 4`.
- Do not add a separate `drivers` table in the first version.
- Add a driver profile later only if drivers need extra data such as vehicle, license, availability, or max delivery count.

## Database Schema

### `delivery_windows`

Stores both recurring weekly windows and specific-date windows.

```text
id
schedule_type string              // weekly, specific_date
day_of_week unsigned tiny integer nullable
delivery_date date nullable
start_time time
end_time time
capacity unsigned integer
is_active boolean default true
branch_id nullable foreign key branches.id
notes nullable text
timestamps
```

Validation rules:

- `schedule_type = weekly` requires `day_of_week` and no `delivery_date`.
- `schedule_type = specific_date` requires `delivery_date` and no `day_of_week`.
- `start_time` must be before `end_time`.
- `capacity` must be at least 1.

Suggested indexes:

```text
index(schedule_type, day_of_week, is_active)
index(schedule_type, delivery_date, is_active)
index(branch_id)
```

### `delivery_window_driver`

Assigns drivers to delivery windows.

```text
id
delivery_window_id foreign key delivery_windows.id
user_id foreign key users.id
timestamps

unique(delivery_window_id, user_id)
```

Rules:

- Assigned users must have `role_id = 4`.
- A window can have zero, one, or many assigned drivers.
- Driver assignment does not control whether the customer sees the window.

### `deliveries`

Stores the delivery fulfillment record for a delivery order.

```text
id
order_id foreign key orders.id unique
delivery_window_id foreign key delivery_windows.id
assigned_driver_id nullable foreign key users.id
delivery_date date
window_start_at timestamp
window_end_at timestamp
address text
latitude decimal(10, 7)
longitude decimal(10, 7)
delivery_instructions nullable text
status string default pending
timestamps
```

Notes:

- `delivery_date`, `window_start_at`, and `window_end_at` are snapshots of what the customer selected.
- `assigned_driver_id` is optional in the first version.
- Window-level drivers say who covers the window; delivery-level driver assignment says who owns a specific order.

Suggested indexes:

```text
index(delivery_window_id, delivery_date)
index(assigned_driver_id)
index(status)
```

## Availability Rules

The client app only needs today's delivery windows.

Resolution order:

1. Resolve today using the application or restaurant timezone.
2. Look for active specific-date windows where `delivery_date = today`.
3. If any specific-date windows exist for today, return only those windows.
4. If no specific-date windows exist for today, return active weekly windows matching today's weekday.
5. For each returned window, calculate remaining capacity from delivery orders already attached to that window/date.
6. Set `is_available = false` when the current time is after the window start time.
7. Set `is_available = false` when remaining capacity is 0.
8. Driver assignment does not affect `is_available`.

Inactive windows should not be returned. Active but unavailable windows should be returned.

Example response:

```json
[
  {
    "delivery_window_id": 12,
    "label": "10:00 AM - 11:30 AM",
    "delivery_date": "2026-08-26",
    "starts_at": "2026-08-26T10:00:00-04:00",
    "ends_at": "2026-08-26T11:30:00-04:00",
    "capacity": 8,
    "remaining_capacity": 3,
    "driver_count": 0,
    "is_available": false,
    "unavailable_reason": "time_passed"
  }
]
```

## Backend Module

Create a delivery module under `app/Services/Delivery`.

Suggested public interface:

```php
DeliveryScheduler::today(): Collection
DeliveryScheduler::reserveForOrder(Order $order, array $deliveryData): Delivery
```

Responsibilities hidden inside the implementation:

- Resolving specific-date windows versus recurring weekly windows.
- Formatting today's customer-facing window list.
- Calculating remaining capacity.
- Marking passed and full windows unavailable.
- Validating that an order-selected window is available.
- Locking the selected window/date to prevent overbooking.
- Creating the `deliveries` row.
- Snapshotting the selected window start/end timestamps.

Keep guest and customer order controllers thin. They should validate request shape and let the delivery module enforce delivery availability.

## Order Creation Integration

Guest checkout and authenticated customer checkout should share the same delivery reservation behavior.

When `is_delivery = true`, require:

```text
delivery_window_id
delivery_address
delivery_latitude
delivery_longitude
```

Optional:

```text
delivery_instructions
```

When `is_delivery = false`:

- Do not require delivery fields.
- Reject `delivery_window_id` or ignore delivery fields consistently.
- Do not create a `deliveries` row.

Order creation should remain transactional:

- Create the order and order items.
- If the order is delivery, reserve the selected window and create the delivery record.
- Preserve existing guest checkout rules:
  - `source = guest-web`
  - `session_id = null`
  - no cash session total mutation
  - idempotency behavior remains unchanged

Idempotency behavior should return the existing order with its delivery relationship loaded.

## Admin CMS

Add a Delivery section under `/admin/delivery-windows`.

Capabilities:

- List delivery windows grouped by recurring weekday and specific date.
- Create weekly recurring windows.
- Create specific-date windows.
- Edit start time, end time, capacity, active state, branch, and notes.
- Assign and remove driver users.
- Show current driver count per window.
- Show today's order count or recent delivery count for each window.

Navigation:

- Add Delivery to desktop admin navigation.
- Add Delivery to mobile admin navigation.

User management:

- Update admin user create/edit pages to use roles from the `roles` table.
- Include Driver as an assignable role.
- Replace hard-coded Admin/User labels where practical.

## API Surface

Customer-facing endpoints:

```text
GET /api/guest/delivery-windows/today
GET /api/me/delivery-windows/today
```

The authenticated customer endpoint can reuse the same response as the guest endpoint.

Order payload additions for delivery orders:

```json
{
  "is_delivery": true,
  "delivery_window_id": 12,
  "delivery_address": "123 Main Road, The Valley",
  "delivery_latitude": 18.2208000,
  "delivery_longitude": -63.0686000,
  "delivery_instructions": "Call on arrival"
}
```

Potential staff API endpoints:

```text
GET /api/delivery-windows
POST /api/delivery-windows
GET /api/delivery-windows/{deliveryWindow}
PUT /api/delivery-windows/{deliveryWindow}
DELETE /api/delivery-windows/{deliveryWindow}
POST /api/delivery-windows/{deliveryWindow}/drivers
DELETE /api/delivery-windows/{deliveryWindow}/drivers/{user}
```

Keep OpenAPI annotations in sync if API endpoints are added.

## Testing Plan

Backend tests:

- Guest can fetch today's recurring delivery windows.
- Authenticated customer can fetch today's recurring delivery windows.
- Specific-date windows for today replace recurring weekday windows.
- Specific-date windows for another date do not affect today's recurring windows.
- Passed windows are returned with `is_available = false`.
- Full windows are returned with `is_available = false`.
- Windows with no assigned driver are still returned.
- Delivery order requires a valid available delivery window.
- Delivery order requires address, latitude, and longitude.
- Pickup order does not create a delivery record.
- Delivery order creates a delivery record with window timestamp snapshots.
- Delivery order cannot reserve a full delivery window.
- Concurrent or repeated delivery reservation cannot overbook capacity.
- Idempotent guest order replay returns the existing delivery order.
- Driver assignment only accepts users with `role_id = 4`.

Admin tests:

- Admin can create a weekly recurring delivery window.
- Admin can create a specific-date delivery window.
- Admin can update capacity and active state.
- Admin can assign a driver to a delivery window.
- Admin can remove a driver from a delivery window.
- Admin user management can assign the Driver role.

Regression tests:

- Existing guest ordering tests still pass.
- Existing POS order cash session behavior remains unchanged.
- Existing admin-only access remains restricted to role `admin`.
- Existing customer-only `/api/me/*` access remains restricted to role `customer`.

## Implementation Phases

### Phase 1: Roles

- Add the `roles` table migration and seed fixed role IDs.
- Add a `Role` model.
- Add `User::role()` relationship.
- Add `Role::users()` relationship.
- Update middleware and admin/user display code gradually to use role codes where practical.
- Keep numeric IDs stable for backwards compatibility.

### Phase 2: Delivery Data Model

- Add `delivery_windows` migration and model.
- Add `delivery_window_driver` pivot migration and relationships.
- Add `deliveries` migration and model.
- Add `Order::delivery()` relationship.
- Add `User::deliveryWindows()` and `User::assignedDeliveries()` relationships.

### Phase 3: Delivery Scheduler Module

- Implement today's window resolver.
- Implement specific-date-over-recurring precedence.
- Implement capacity calculation.
- Implement availability flags for time-passed and full windows.
- Implement transactional reservation with a lock.

### Phase 4: Customer-Facing API

- Add guest and authenticated customer endpoints for today's delivery windows.
- Add delivery fields to guest and authenticated customer order request validation.
- Integrate delivery reservation into `OrderCreationService`.
- Load delivery data in order responses where relevant.

### Phase 5: Admin CMS

- Add delivery window admin controller.
- Add Blade views for listing, creating, editing, and assigning drivers.
- Update desktop and mobile admin navigation.
- Update user create/edit role selection to include Driver.

### Phase 6: API Docs

- Add OpenAPI annotations for delivery endpoints.
- Regenerate Swagger docs with `php artisan l5-swagger:generate`.

## Open Questions

- Which timezone should define "today" for delivery availability if the server timezone changes?
- Should `branch_id` be required immediately, or nullable until branch-specific delivery is needed?
- Should specific-date windows replace recurring windows only when at least one active specific-date window exists, or when any specific-date window exists even if all are inactive?
- Should deleting a delivery window be blocked when existing delivery orders reference it?
- Should admins be able to manually reassign an existing delivery order to another window after it is placed?
