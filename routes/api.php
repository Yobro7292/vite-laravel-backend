<?php

use App\Http\Controllers\userController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/
Route::post('/forget-password', [AuthController::class, 'forgetPassword']);
Route::get('/reset-password', [AuthController::class, 'verifyToken']);
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware(['auth:sanctum'])->group(function () {
    Route::controller(userController::class)->group(
        function () {
            Route::get('/get-users', 'getUsers');
            Route::get('/get-user', 'getUsersById');
            Route::post('/set-user', 'setUser');
            Route::delete('/remove-user', 'removeUser');
        }
    );
    Route::post('/change-password', [AuthController::class, 'changePassword']);
    Route::post('/logout', [AuthController::class, 'logout']);
});