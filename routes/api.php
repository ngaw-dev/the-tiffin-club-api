<?php

use App\Http\Controllers\Api\Admin\AuthController;
use App\Http\Controllers\Api\Admin\CustomerController;
use App\Http\Controllers\Api\Admin\GlobalSettingController;
use App\Http\Controllers\Api\Admin\MenuController;
use App\Http\Controllers\Api\Admin\OrderController;
use App\Http\Controllers\Api\Admin\PaymentController;
use App\Http\Controllers\Api\GlobalSettingController as PublicGlobalSettingController;
use App\Http\Controllers\Api\MenuController as PublicMenuController;
use App\Http\Controllers\Api\MyAccountController;
use App\Http\Controllers\Api\MyOrdersController;
use App\Http\Controllers\Api\OrderController as PublicOrderController;
use App\Http\Controllers\Api\OrderLookupController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    // Public customer endpoints are rate limited per IP; admin routes stay on the looser api limiter.
    Route::middleware('throttle:customer')->group(function () {
        Route::get('/menu/today', [PublicMenuController::class, 'today'])->name('menu.today');
        Route::get('/settings', [PublicGlobalSettingController::class, 'index'])->name('settings.index');
        Route::post('/orders', [PublicOrderController::class, 'store'])->name('orders.store');
        Route::post('/orders/lookup', OrderLookupController::class)->name('orders.lookup');
        Route::get('/orders/{token}', [PublicOrderController::class, 'show'])->name('orders.show');
        Route::patch('/orders/{token}', [PublicOrderController::class, 'update'])->name('orders.update');
        Route::delete('/orders/{token}', [PublicOrderController::class, 'destroy'])->name('orders.destroy');
    });

    Route::middleware('clerk.auth')->group(function () {
        Route::get('/my-account', [MyAccountController::class, 'show'])->name('my-account.show');
        Route::patch('/my-account', [MyAccountController::class, 'update'])->name('my-account.update');
        Route::get('/my-orders', [MyOrdersController::class, 'index'])->name('my-orders.index');
    });

    Route::prefix('admin')->group(function () {
        Route::post('/login', [AuthController::class, 'login'])->name('admin.login');

        Route::middleware(['auth:sanctum', 'staff.email'])->group(function () {
            Route::post('/logout', [AuthController::class, 'logout'])->name('admin.logout');

            Route::get('/orders', [OrderController::class, 'index'])->name('admin.orders.index');
            Route::get('/orders/{order}', [OrderController::class, 'show'])->name('admin.orders.show');
            Route::patch('/orders/{order}', [OrderController::class, 'update'])->name('admin.orders.update');

            Route::get('/payments', [PaymentController::class, 'index'])->name('admin.payments.index');
            Route::post('/payments', [PaymentController::class, 'store'])->name('admin.payments.store');

            Route::get('/customers', [CustomerController::class, 'index'])->name('admin.customers.index');
            Route::get('/customers/{customer}', [CustomerController::class, 'show'])->name('admin.customers.show');
            Route::patch('/customers/{customer}', [CustomerController::class, 'update'])->name('admin.customers.update');

            Route::get('/menus', [MenuController::class, 'index'])->name('admin.menus.index');
            Route::post('/menus', [MenuController::class, 'store'])->name('admin.menus.store');
            Route::get('/menus/{menu}', [MenuController::class, 'show'])->name('admin.menus.show');
            Route::patch('/menus/{menu}', [MenuController::class, 'update'])->name('admin.menus.update');
            Route::delete('/menus/{menu}', [MenuController::class, 'destroy'])->name('admin.menus.destroy');
            Route::post('/menus/{menu}/items', [MenuController::class, 'storeItem'])->name('admin.menu-items.store');
            Route::patch('/menus/{menu}/items/{item}', [MenuController::class, 'updateItem'])->name('admin.menu-items.update');
            Route::delete('/menus/{menu}/items/{item}', [MenuController::class, 'destroyItem'])->name('admin.menu-items.destroy');

            Route::get('/settings', [GlobalSettingController::class, 'index'])->name('admin.settings.index');
            Route::put('/settings', [GlobalSettingController::class, 'update'])->name('admin.settings.update');
        });
    });
});
