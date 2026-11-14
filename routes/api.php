<?php

use App\Http\Controllers\Api\MenuController;
use App\Http\Controllers\Api\OrderController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::get('/menu/today', [MenuController::class, 'today'])->name('menu.today');
    Route::post('/orders', [OrderController::class, 'store'])->name('orders.store');
    Route::get('/orders/{token}', [OrderController::class, 'show'])->name('orders.show');
    Route::patch('/orders/{token}', [OrderController::class, 'update'])->name('orders.update');
    Route::delete('/orders/{token}', [OrderController::class, 'destroy'])->name('orders.destroy');
});
