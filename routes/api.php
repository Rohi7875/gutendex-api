<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\BookController;

Route::prefix('v1')->group(function () {
    Route::get('/books', [BookController::class, 'index']);
});
