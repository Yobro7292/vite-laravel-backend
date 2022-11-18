<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class userController extends Controller
{
    /* ------------- Get Users after login ----------- */
    public function getUsers(Request $request)
    {
        $authUser = Auth::user();
        $users = DB::select('select * from users');
        $users['success'] = true;
        return response()->json($users, 200);
    }

    /* ------------- Get users by id  ----------- */
    public function getUsersById(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'id' => 'required'
        ]);
        if ($validate->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'User Id is required'
            ], 400);
        }
        $authUser = Auth::user();
        $user = DB::select('select * from users where id=?', [$request->id]);
        $user['success'] = true;
        if (count($user) > 1) {
            return response()->json($user, 200);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'User not found'
            ], 400);
        }
    }

    /* ------------- update user by email ----------- */
    public function setUser(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'email' => 'required',
            'name' => 'required'
        ]);
        if ($validate->fails()) {
            return response()->json([
                'success' => false,
                $validate->errors()
            ], 400);
        }
        $authUser = Auth::user();
        $update = DB::update('update users set email = ?, name = ?, updated_at = ? where email = ?', [$request->email, $request->name, Carbon::now(), $authUser->email]);
        $updatedUser = DB::select('select * from users where email=?', [$request->email]);
        if ($update) {
            return response()->json([
                'success' => true,
                $updatedUser,
                'message' => 'User updated'
            ], 200);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Something went wrong while updating user'
            ], 400);
        }
    }

    /* ------------- delete user by id ----------- */
    public function removeUser(Request $request)
    {
        $authUser = Auth::user();
        Auth::user()->tokens()->delete();
        $deleteUser = DB::delete('delete from users where email = ?', [$authUser->email]);

        if ($deleteUser) {
            return response()->json([
                'success' => true,
                'message' => 'User deleted'
            ], 200);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Something went wrong while removing user'
            ], 400);
        }
    }
}