<?php

use App\Http\Controllers\Admin\ApiDocsController;
use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\BranchController;
use App\Http\Controllers\Admin\CashSessionController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\CustomerController;
use App\Http\Controllers\Admin\CustomerReferralController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\DeliveryWindowController;
use App\Http\Controllers\Admin\DownloadController;
use App\Http\Controllers\Admin\ItemController;
use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\Admin\PaymentController;
use App\Http\Controllers\Admin\ReferralProgramController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\RewardController;
use App\Http\Controllers\Admin\TipController;
use App\Http\Controllers\Admin\UserController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return Auth::check()
        ? redirect()->route('admin.dashboard')
        : redirect()->route('admin.login');
    // return view('welcome');
});

// Admin routes
Route::prefix('admin')->name('admin.')->group(function () {
    // Guest routes
    Route::middleware('guest')->group(function () {
        Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
        Route::post('/login', [AuthController::class, 'login']);
    });

    // Authenticated admin routes
    Route::middleware(['auth', 'admin'])->group(function () {
        Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

        // Users management
        Route::resource('users', UserController::class);
        Route::delete('/users/{user}/tokens/{tokenId}', [UserController::class, 'revokeToken'])->name('users.revoke-token');
        Route::delete('/users/{user}/tokens', [UserController::class, 'revokeAllTokens'])->name('users.revoke-all-tokens');

        // Data management
        Route::get('/categories', [CategoryController::class, 'index'])->name('categories.index');
        Route::post('/categories', [CategoryController::class, 'store'])->name('categories.store');
        Route::put('/categories/{category}', [CategoryController::class, 'update'])->name('categories.update');
        Route::delete('/categories/{category}', [CategoryController::class, 'destroy'])->name('categories.destroy');

        // Branches management
        Route::resource('branches', BranchController::class);

        // Delivery windows management
        Route::post('/delivery-windows/{delivery_window}/duplicate', [DeliveryWindowController::class, 'duplicate'])->name('delivery-windows.duplicate');
        Route::resource('delivery-windows', DeliveryWindowController::class, ['only' => ['index', 'store', 'update', 'destroy']]);

        // Orders management
        Route::resource('orders', OrderController::class, ['only' => ['index', 'show', 'update', 'destroy']]);

        // Sessions management
        Route::resource('sessions', CashSessionController::class, ['only' => ['index', 'show', 'destroy']]);

        // Customers management
        Route::resource('customers', CustomerController::class, ['only' => ['index', 'store', 'show', 'update', 'destroy']]);
        Route::post('/customers/{customer}/reward-adjustments', [\App\Http\Controllers\Admin\CustomerRewardController::class, 'storeAdjustment'])->name('customers.reward-adjustments.store');
        Route::post('/customers/{customer}/referral-adjustments', [CustomerReferralController::class, 'storeAdjustment'])->name('customers.referral-adjustments.store');
        Route::post('/customers/{customer}/referrals/{referral}/reverse', [CustomerReferralController::class, 'reverse'])->name('customers.referrals.reverse');

        // Tips management
        Route::resource('tips', TipController::class, ['only' => ['index', 'show']]);

        // Payments management
        Route::resource('payments', PaymentController::class, ['only' => ['index', 'show', 'destroy']]);

        // Reports
        Route::get('/reports', [ReportController::class, 'index'])->name('reports');

        // Rewards
        Route::get('/rewards', [RewardController::class, 'index'])->name('rewards.index');
        Route::post('/rewards', [RewardController::class, 'store'])->name('rewards.store');
        Route::put('/rewards/{reward}', [RewardController::class, 'update'])->name('rewards.update');
        Route::patch('/rewards/{reward}/activate', [RewardController::class, 'activate'])->name('rewards.activate');
        Route::patch('/rewards/{reward}/deactivate', [RewardController::class, 'deactivate'])->name('rewards.deactivate');
        Route::delete('/rewards/{reward}', [RewardController::class, 'destroy'])->name('rewards.destroy');
        Route::post('/referral-rewards', [ReferralProgramController::class, 'store'])->name('referral-rewards.store');
        Route::put('/referral-rewards/{referralReward}', [ReferralProgramController::class, 'update'])->name('referral-rewards.update');
        Route::patch('/referral-rewards/{referralReward}/activate', [ReferralProgramController::class, 'activate'])->name('referral-rewards.activate');
        Route::patch('/referral-rewards/{referralReward}/deactivate', [ReferralProgramController::class, 'deactivate'])->name('referral-rewards.deactivate');
        Route::delete('/referral-rewards/{referralReward}', [ReferralProgramController::class, 'destroy'])->name('referral-rewards.destroy');

        // Downloads
        Route::resource('downloads', DownloadController::class, ['only' => ['index', 'create', 'store', 'edit', 'update']]);

        // Options management
        Route::resource('options', \App\Http\Controllers\Admin\OptionController::class);
        Route::post('/options/{option}/values', [\App\Http\Controllers\Admin\OptionController::class, 'storeValue'])->name('option-values.store');
        Route::put('/options/{option}/values/{optionValue}', [\App\Http\Controllers\Admin\OptionController::class, 'updateValue'])->name('option-values.update');
        Route::delete('/options/{option}/values/{optionValue}', [\App\Http\Controllers\Admin\OptionController::class, 'destroyValue'])->name('option-values.destroy');

        // Items management
        Route::resource('items', ItemController::class, ['only' => ['index', 'store', 'update', 'destroy']]);

        Route::post('/items/{item}/option-values', [ItemController::class, 'updateOptionValues'])->name('items.update-option-values');

        Route::patch('/items/{item}/item-options/order', [ItemController::class, 'updateItemOptionOrder'])->name('items.item-options.update-order');

        Route::patch('/items/{item}/item-options/{itemOption}', [ItemController::class, 'updateItemOptionQty'])->name('items.item-options.update-qty');

        Route::delete('/items/{item}/item-options/{itemOption}', [ItemController::class, 'destroyItemOption'])->name('items.item-options.destroy');

        // API Documentation
        Route::get('/api-docs', [ApiDocsController::class, 'index'])->name('api-docs');
    });
});
