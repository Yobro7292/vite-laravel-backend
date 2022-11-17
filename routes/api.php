<?php

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
Route::middleware('auth:sanctum')->get('/get-users', [AuthController::class, 'getUsers']);
Route::middleware('auth:sanctum')->post('/logout', [AuthController::class, 'logout']);