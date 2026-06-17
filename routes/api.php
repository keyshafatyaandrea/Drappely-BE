<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\TransactionController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\ReportController;


Route::group(['prefix' => 'auth'], function () {

    Route::post('login', [AuthController::class, 'login']);

    Route::middleware('auth:api')->group(function () {
        Route::post('register', [AuthController::class, 'register']);
        Route::get('user', [AuthController::class, 'user']);
        Route::post('refresh', [AuthController::class, 'refresh']);
        Route::post('logout', [AuthController::class, 'logout']);
        Route::post('change-password', [AuthController::class, 'changePassword']);
    });
});

// crud product
// `Route::get(..., [ProductController::class, 'index'])` itu cara Laravel
// bilang: pakai ProductController terus jalankan method index.
Route::get('/products', [ProductController::class, 'index']);
Route::post('products', [ProductController::class, 'store']);  
Route::get('products/{id}', [ProductController::class, 'show']);
Route::put('products/{id}', [ProductController::class, 'update']);
Route::delete('products/{id}', [ProductController::class, 'destroy']);
Route::get('products/categories', [ProductController::class, 'categories']);
Route::get('products/top-selling', [ProductController::class, 'topSelling']);

//transaksi
Route::post('transactions', [TransactionController::class, 'store']);
Route::get('transactions', [TransactionController::class, 'index']);
Route::get('transactions/{id}', [TransactionController::class, 'show']);

//dashboard
Route::get('/dashboard/summary', [TransactionController::class, 'getTodaySummary']);
Route::get('/dashboard/charts/daily-sales', [TransactionController::class, 'getDailyAnalytics']);
Route::get('dashboard/charts/monthly-sales', [TransactionController::class, 'getMonthlyAnalytics']);
Route::get('dashboard/charts/profit', [TransactionController::class, 'getProfitAnalytics']);
Route::get('dashboard/top-products', [ProductController::class, 'productSelling']);
Route::get('dashboard/recent-transactions', [TransactionController::class, 'recentTransactions']);

//kategori product
Route::apiResource('categories', CategoryController::class);

//export struk / laporan transaksi
Route::prefix('reports')->group(function () {
    Route::get('receipt/{transaction_id}', [ReportController::class, 'downloadReceiptPdf']);
});
