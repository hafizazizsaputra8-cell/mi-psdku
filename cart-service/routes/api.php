<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CartController;

// Menampilkan keranjang
Route::get('/carts/{userId}', [CartController::class, 'show'])
    ->whereNumber('userId');

// Menambahkan item ke keranjang
Route::post('/carts', [CartController::class, 'store']);

// Mengubah jumlah item
Route::put('/carts/items/{id}', [CartController::class, 'update'])
    ->whereNumber('id');

// Menghapus satu item
Route::delete('/carts/items/{id}', [CartController::class, 'destroy'])
    ->whereNumber('id');

// Mengosongkan seluruh keranjang
Route::delete('/carts/{userId}', [CartController::class, 'clear'])
    ->whereNumber('userId');