<?php

use App\Http\Controllers\MovementController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\WarehouseController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::prefix('v1')->group(function () {

    // Маршруты для управления товарами
    Route::apiResource('products', ProductController::class);

    // Маршруты для управления заказами
    Route::apiResource('orders', OrderController::class)->except(['destroy']);

    // Маршруты для управления складами
    Route::apiResource('warehouses', WarehouseController::class)->only(['index', 'show']);

    // Маршруты для истории движений
    Route::get('movements', [MovementController::class, 'index'])->name('movements.index');
    Route::post('movements', [MovementController::class, 'store'])->name('movements.store');
});
