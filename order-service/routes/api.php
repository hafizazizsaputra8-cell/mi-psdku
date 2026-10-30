<?php

use App\Http\Controllers\HealthController;
use App\Http\Controllers\OrderController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for order-service. These
| routes are loaded by the RouteServiceProvider within a group which
| is configured with apiPrefix: '' to match microservice specifications.
|
*/

// Health check endpoint
Route::get('/health', [HealthController::class, 'index']);

// Checkout: membuat pesanan dari isi keranjang (201, 409, 422, 503, 500)
Route::post('/orders', [OrderController::class, 'store']);

// Detail satu pesanan beserta item-itemnya (200, 404, 500)
Route::get('/orders/{id}', [OrderController::class, 'show']);

// Daftar pesanan satu user, terbaru di atas (200, 422, 500)
Route::get('/orders/user/{userId}', [OrderController::class, 'byUser']);

// Bayar (simulasi): PENDING menjadi PAID (200, 404, 409, 500)
Route::patch('/orders/{id}/pay', [OrderController::class, 'pay']);
