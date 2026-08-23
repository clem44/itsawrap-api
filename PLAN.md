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
