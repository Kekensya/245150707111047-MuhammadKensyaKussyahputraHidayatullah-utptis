<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ItemController;

Route::prefix('items')->group(function () {
    Route::get('/',        [ItemController::class, 'index']);    // GET semua
    Route::get('/{id}',   [ItemController::class, 'show']);     // GET by ID
    Route::post('/',      [ItemController::class, 'store']);    // POST buat baru
    Route::put('/{id}',   [ItemController::class, 'update']);   // PUT update penuh
    Route::patch('/{id}', [ItemController::class, 'patch']);    // PATCH update sebagian
    Route::delete('/{id}',[ItemController::class, 'destroy']);  // DELETE hapus
});