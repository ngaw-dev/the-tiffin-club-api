<?php

use App\Http\Controllers\Staff\AuthController as StaffAuthController;
use App\Http\Controllers\Staff\OrderController as StaffOrderController;
use App\Http\Controllers\Staff\PaymentController as StaffPaymentController;
use App\Http\Middleware\StaffEmailMiddleware;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::prefix('staff')->group(function (): void {
    Route::get('/login', [StaffAuthController::class, 'showLogin'])->name('staff.login');
    Route::post('/login', [StaffAuthController::class, 'login']);
    Route::post('/logout', [StaffAuthController::class, 'logout'])->name('staff.logout');

    Route::middleware(['auth', StaffEmailMiddleware::class])->group(function (): void {
        Route::get('/orders', [StaffOrderController::class, 'index'])->name('staff.orders');
        Route::get('/payments', [StaffPaymentController::class, 'index'])->name('staff.payments');
        Route::patch('/orders/{order}/status', [StaffOrderController::class, 'updateStatus'])->name('staff.orders.status');
        Route::post('/orders/{order}/payments', [StaffOrderController::class, 'pay'])->name('staff.orders.pay');
    });
});
