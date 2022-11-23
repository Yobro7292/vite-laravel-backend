<?php
use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
*/
Route::get('/reset-password', [AuthController::class, 'verifyEmailToken']);
Route::post('/reset-password', [AuthController::class, 'resetPassword']);