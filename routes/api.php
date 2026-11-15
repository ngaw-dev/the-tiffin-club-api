<?php

use App\Http\Controllers\Api\MenuController;
use App\Http\Controllers\Api\MyAccountController;
use App\Http\Controllers\Api\MyOrdersController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\OrderLookupController;
use App\Http\Controllers\Api\SettingController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::get('/menu/today', [MenuController::class, 'today'])->name('menu.today');
    Route::get('/settings', [SettingController::class, 'index'])->name('settings.index');
    Route::post('/orders', [OrderController::class, 'store'])->name('orders.store');
    Route::post('/orders/lookup', OrderLookupController::class)->name('orders.lookup');
    Route::get('/orders/{token}', [OrderController::class, 'show'])->name('orders.show');
    Route::patch('/orders/{token}', [OrderController::class, 'update'])->name('orders.update');
    Route::delete('/orders/{token}', [OrderController::class, 'destroy'])->name('orders.destroy');

    Route::middleware('clerk.auth')->group(function () {
        Route::get('/my-account', [MyAccountController::class, 'show'])->name('my-account.show');
        Route::patch('/my-account', [MyAccountController::class, 'update'])->name('my-account.update');
        Route::get('/my-orders', [MyOrdersController::class, 'index'])->name('my-orders.index');
    });
});
