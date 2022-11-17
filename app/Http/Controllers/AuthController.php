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
            $host = $request->getSchemeAndHttpHost();
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
            return response()->json('User not exists', 400);
        }

    }

    /* ------------- Verify Token----------- */
    public function verifyToken(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'token' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }
        $data = [];
        $check = DB::table('password_resets')->where([
            ['email', $request->all()['email']],
            ['token', $request->all()['token']],
        ]);
        if ($check->exists()) {
            $difference = Carbon::now()->diffInSeconds($check->first()->created_at);
            if ($difference > 3600) {
                $data['isVerified'] = false;
                return view('resetPasswordForm.index', $data);
            }

            $data['token'] = $request->all()['token'];
            $data['email'] = $request->all()['email'];
            $data['isVerified'] = true;
            $data['url'] = '/reset-password';
            $data['isPasswordSet'] = false;
            return view('resetPasswordForm.index', $data);
        } else {
            $data = [];
            $data['isVerified'] = false;
            return view('resetPasswordForm.index', $data);
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
            return response()->json($validator->errors(), 400);
        }

        if ($request->all()['newPassword'] != $request->all()['confirmNewPassword']) {
            $data['isVerified'] = false;
            return view('resetPasswordForm.index', $data);
        }
        $data = [];

        $check = DB::table('password_resets')->where([
            ['email', $request->all()['email']],
            ['token', $request->all()['token']],
        ]);
        if ($check->exists()) {
            $difference = Carbon::now()->diffInSeconds($check->first()->created_at);
            if ($difference > 3600) {
                $data['isVerified'] = false;
                return view('resetPasswordForm.index', $data);
            }

            $password = Hash::make($request->all()['newPassword']);


            DB::update('update users set password = ? where email = ?', [$password, $request->all()['email']]);

            $delete = DB::table('password_resets')->where([
                ['email', $request->all()['email']],
                ['token', $request->all()['token']],
            ])->delete();

            $data['isPasswordSet'] = true;
            $data['isVerified'] = true;
            return view('resetPasswordForm.index', $data);
        } else {
            $data = [];
            $data['isVerified'] = false;
            return view('resetPasswordForm.index', $data);
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
            return response()->json('Password should match with Confirm Password field');
        }
        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }
        $input = $request->all();
        $input['password'] = Hash::make($input['password']);
        unset($input['confirm_password']);
        $user = User::create($input);
        $success['token'] = $user->createToken('MyAuthApp')->plainTextToken;
        $success['message'] = 'User ' . $user['name'] . ' created successfully';
        $success['success'] = true;
        return response()->json($success, 200);
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
        return response()->json('Logged Out', 200);
    }

    /* ------------- Get User after login ----------- */
    public function getUsers(Request $request)
    {
        $authUser = Auth::user();
        $users = DB::select('select * from users');
        return response()->json($users, 200);
    }
}