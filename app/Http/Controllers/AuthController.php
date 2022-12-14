<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use App\Mail\EmailDemo;
use Symfony\Component\HttpFoundation\Response;
use Validator;
use Str;
use App\Models\User;
use \Laravel\Sanctum\PersonalAccessToken;

class AuthController extends Controller
{
    /* ------------- Password Reset and Mail Function ----------- */
    public function forgetPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email'
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }
        $verify = User::where('email', $request->all()['email'])->exists();
        if ($verify) {
            $verify2 = DB::table('password_resets')->where([
                ['email', $request->all()['email']]
            ]);

            if ($verify2->exists()) {
                $verify2->delete();
            }

            $token = Str::random(64);
            $email = $request->email;
            $host = request()->headers->get("origin");
            $url = $host . '/reset-password?token=' . $token . '&email=' . $email;
            $password_reset = DB::table('password_resets')->insert([
                'email' => $email,
                'token' => $token,
                'created_at' => Carbon::now()
            ]);

            if ($password_reset) {
                $mailData = [
                    'title' => 'Reseting Password',
                    'url' => $url
                ];
                Mail::to($email)->send(new EmailDemo($mailData));
            }
            return response()->json([
                'success' => true,
                'message' => 'Email sent successfully'
            ], 200);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'User does not exists'
            ], 400);
        }
    }
    /* ------------ verify user token ------------- */
    public function verifyUserToken(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'token' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        $personalAccessToken = PersonalAccessToken::findToken($request->token);
        // if($personalAccessToken)
        if ($personalAccessToken) {
            $user = $personalAccessToken->tokenable;
            return response()->json([
                'success' => true,
                'message' => 'token verified',
                'user' => $user
            ], 200);
        } else {
            $success['success'] = false;
            $success['message'] = 'Invalid token';
            return response()->json($success, 400);
        }
    }

    /* ------------- Verify Email Token----------- */
    public function verifyEmailToken(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'token' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }
        $check = DB::table('password_resets')->where([
            ['email', $request->all()['email']],
            ['token', $request->all()['token']],
        ]);
        if ($check->exists()) {
            $difference = Carbon::now()->diffInSeconds($check->first()->created_at);
            //valid only for five minutes
            if ($difference > 300) {
                return response()->json([
                    'success' => false,
                    'message' => 'Token Expired'
                ], 400);
            } else {
                return response()->json([
                    'success' => true,
                    'message' => 'Token Verified'
                ], 200);
            }
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Invalid Token'
            ], 400);
        }
    }

    /* ------------- Update Password Function ----------- */
    public function resetPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'token' => 'required',
            'newPassword' => 'required',
            'confirmNewPassword' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                $validator->errors()
            ], 400);
        }

        if ($request->all()['newPassword'] != $request->all()['confirmNewPassword']) {
            return response()->json([
                'success' => false,
                'message' => 'Password should match with Confirm Password field'
            ], 400);
        }
        $check = DB::table('password_resets')->where([
            ['email', $request->all()['email']],
            ['token', $request->all()['token']],
        ]);
        if ($check->exists()) {
            $difference = Carbon::now()->diffInSeconds($check->first()->created_at);
            if ($difference > 300) {
                return response()->json([
                    'success' => false,
                    'message' => 'Token Expired'
                ], 400);
            }
            $password = Hash::make($request->all()['newPassword']);
            DB::update('update users set password = ? where email = ?', [$password, $request->all()['email']]);

            DB::table('password_resets')->where([
                ['email', $request->all()['email']],
                ['token', $request->all()['token']],
            ])->delete();
            return response()->json([
                'success' => true,
                'message' => 'Password changed'
            ], 200);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Invalid Token'
            ], 400);
        }
    }

    /* ------------- Register Function ----------- */
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'name' => 'required',
            'password' => 'required',
            'confirm_password' => 'required'
        ]);
        if ($request->confirm_password != $request->password) {
            return response()->json([
                'message' => 'Password should match with Confirm Password field',
                'success' => false
            ], 400);
        }
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                $validator->errors()
            ], 400);
        }
        $input = $request->all();
        $input['password'] = Hash::make($input['password']);
        unset($input['confirm_password']);

        $found = User::where('email', $input['email'])->count();
        if ($found == 0) {
            $user = User::create($input);
            $success['token'] = $user->createToken('MyAuthApp')->plainTextToken;
            $success['message'] = 'User ' . $user['name'] . ' created successfully';
            $success['success'] = true;
            $success['user'] = $user;
            return response()->json($success, 200);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'User already registered'
            ], 400);
        }
    }

    /* ------------- Login Function ----------- */
    public function login(Request $request)
    {
        $auth = Auth::attempt(['email' => $request->email, 'password' => $request->password]);
        if ($auth) {
            $authUser = Auth::user();
            $success['user'] = $authUser;
            $success['success'] = true;
            $success['token'] = $authUser->createToken('MyAuthApp')->plainTextToken;
            $success['message'] = 'Login success';
            return response()->json($success, 200);
        } else {
            $success['success'] = false;
            $success['message'] = 'Invalid password or User not found';
            return response()->json($success, 400);
        }
    }

    /* ------------- LogOut Function ----------- */
    public function logout(Request $request)
    {
        Auth::user()->tokens()->delete();
        return response()->json([
            'success' => true,
            'message' => 'Logged Out'
        ], 200);
    }

    /* ------------- Change Password Function ----------- */
    public function changePassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'current_password' => 'required',
            'new_password' => 'required',
            'confirm_new_password' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                $validator->errors()
            ], 400);
        }
        if ($request->new_password != $request->confirm_new_password) {
            return response()->json([
                'message' => 'Password should match with Confirm Password field',
                'success' => false
            ], 400);
        }

        $authUser = Auth::user();
        $auth = Hash::check($request->current_password, $authUser->password);
        if ($auth) {
            $newPassword = Hash::make($request->new_password);
            $update = DB::update('update users set password = ? where email = ?', [$newPassword, $authUser->email]);
            if ($update) {

                // for logout
                Auth::user()->tokens()->delete();

                return response()->json([
                    'success' => true,
                    'message' => 'Password updated'
                ], 200);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Something went wrong while updating password'
                ], 400);
            }
        } else {
            $success['success'] = false;
            $success['message'] = 'Invalid current password.';
            return response()->json($success, 400);
        }
    }
}
