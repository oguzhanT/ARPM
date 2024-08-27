<?php

use App\Http\Controllers\OrderController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('orders', [OrderController::class, 'index']);
Route::get('create-collection', [OrderController::class, 'createCollection']);
