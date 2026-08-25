<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BranchController;
use App\Http\Controllers\Api\CashSessionController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\CustomerController;
use App\Http\Controllers\Api\CustomerOrderController;
use App\Http\Controllers\Api\CustomerRewardController;
use App\Http\Controllers\Api\GuestCustomerController;
use App\Http\Controllers\Api\GuestMenuController;
use App\Http\Controllers\Api\GuestOrderController;
use App\Http\Controllers\Api\GuestRegistrationController;
use App\Http\Controllers\Api\ItemController;
use App\Http\Controllers\Api\ItemOptionController;
use App\Http\Controllers\Api\ItemOptionValueController;
use App\Http\Controllers\Api\OptionController;
use App\Http\Controllers\Api\OptionDependencyController;
use App\Http\Controllers\Api\OptionValueController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\OrderItemController;
use App\Http\Controllers\Api\OrderRewardController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\PushSubscriptionController;
use App\Http\Controllers\Api\RewardProgramController;
use App\Http\Controllers\Api\SettingController;
use App\Http\Controllers\Api\StatusController;
use App\Http\Controllers\Api\TaxController;
use App\Http\Controllers\Api\TipController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\WithdrawalController;
use Illuminate\Support\Facades\Route;

// Public routes
Route::post('/login', [AuthController::class, 'login']);

Route::prefix('guest')->group(function () {
    Route::middleware('throttle:guest-menu')->get('/menu', GuestMenuController::class);
    Route::middleware('throttle:guest-customer')->post('/customers', [GuestCustomerController::class, 'store']);
    Route::middleware('throttle:guest-order')->post('/orders', [GuestOrderController::class, 'store']);
    Route::middleware('throttle:guest-register')->post('/register', [GuestRegistrationController::class, 'store']);
});

// Authenticated routes shared by every role (staff and customers alike)
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);
    Route::post('/push-subscriptions', [PushSubscriptionController::class, 'store']);
    Route::delete('/push-subscriptions/{pushSubscription}', [PushSubscriptionController::class, 'destroy']);
});

// Customer self-service routes — identity resolved from the token, never from a route parameter
Route::middleware(['auth:sanctum', 'customer'])->prefix('me')->group(function () {
    Route::get('/rewards', [CustomerRewardController::class, 'me']);
    Route::post('/orders', [CustomerOrderController::class, 'store']);
});

// Staff-only (POS / admin) routes
Route::middleware(['auth:sanctum', 'staff'])->group(function () {
    Route::get('/users/username-exists', [UserController::class, 'usernameExists']);
    Route::get('/users/email-exists', [UserController::class, 'emailExists']);
    Route::apiResource('users', UserController::class);
    Route::apiResource('categories', CategoryController::class);
    Route::post('/items/{item}/options', [ItemController::class, 'syncOptions']);
    Route::apiResource('items', ItemController::class);
    Route::apiResource('options', OptionController::class);
    Route::apiResource('option-values', OptionValueController::class);
    Route::apiResource('item-options', ItemOptionController::class);
    Route::apiResource('item-option-values', ItemOptionValueController::class);
    Route::apiResource('item_options', ItemOptionController::class);
    Route::apiResource('item_option_values', ItemOptionValueController::class);
    Route::apiResource('option_dependencies', OptionDependencyController::class);
    Route::apiResource('taxes', TaxController::class);
    Route::get('/rewards/programs', [RewardProgramController::class, 'index']);
    Route::get('/customers/{customer}/rewards', [CustomerRewardController::class, 'show']);
    Route::apiResource('customers', CustomerController::class);
    Route::get('/sessions/current', [CashSessionController::class, 'current']);
    Route::post('/sessions/{cashSession}/close', [CashSessionController::class, 'close']);
    Route::patch('/sessions/{cashSession}/totals', [CashSessionController::class, 'updateTotals']);
    Route::apiResource('sessions', CashSessionController::class)->only(['index', 'store', 'show']);
    Route::get('/orders/history', [OrderController::class, 'history']);
    Route::post('/orders/{order}/rewards/redeem', [OrderRewardController::class, 'store']);
    Route::apiResource('orders', OrderController::class);
    Route::apiResource('order-items', OrderItemController::class);
    Route::apiResource('payments', PaymentController::class);
    Route::apiResource('tips', TipController::class)->only(['index', 'store', 'show', 'update', 'destroy']);
    Route::apiResource('withdrawals', WithdrawalController::class)->only(['index', 'store', 'show', 'destroy']);
    Route::get('/settings', [SettingController::class, 'index']);
    Route::get('/settings/{key}', [SettingController::class, 'show']);
    Route::put('/settings/{key}', [SettingController::class, 'update']);
    Route::post('/settings/bulk', [SettingController::class, 'bulkUpdate']);
    Route::apiResource('statuses', StatusController::class);
    Route::post('/branches/{branch}/items', [BranchController::class, 'syncItems']);
    Route::apiResource('branches', BranchController::class);
});
