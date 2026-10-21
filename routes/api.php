<?php

use App\Http\Controllers\StockController;
use Illuminate\Support\Facades\Route;

Route::post('batches', [StockController::class, 'purchase']);
Route::post('batches/{batch}/refund', [StockController::class, 'refund']);
Route::get('batches/profit', [StockController::class, 'profit']);
Route::get('products', [StockController::class, 'products']);
Route::post('orders', [StockController::class, 'order']);
Route::get('stock', [StockController::class, 'stock']);
