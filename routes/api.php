<?php

use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('users', [UserController::class, 'index']);
Route::post('users', [UserController::class, 'store']);
Route::post('users/{user}/profile/image', [UserController::class, 'storeImage']);
Route::post('users/{user}/schedule', [UserController::class, 'storeSchedule']);
