<?php
use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
*/
Route::get('/reset-password', [AuthController::class, 'verifyToken']);
Route::post('/reset-password', [AuthController::class, 'resetPassword']);
// Route::get('/set', function () {
//     $data['isVerified'] = true;
//     $data['token'] = '25djhajksfhajfklgkllhdgkasufgek';
//     $data['url'] = '#';
//     return view('resetPasswordForm.index', $data);
// });