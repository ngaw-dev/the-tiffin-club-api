<?php

use App\Http\Controllers\Api\MenuController;
use App\Http\Controllers\Api\OrderController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::get('/menu/today', [MenuController::class, 'today'])->name('menu.today');
    Route::post('/orders', [OrderController::class, 'store'])->name('orders.store');
});
