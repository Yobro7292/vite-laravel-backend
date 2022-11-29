<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Models\Naikan;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class NaikanController extends Controller
{
    /* ------------- Get Naikans ----------- */
    public function index()
    {
        $authUser = Auth::user();
        $naikans = Naikan::where([['user_id', '=', $authUser['id']]])->paginate(5);

        if ($naikans->count() <= 0) {
            return response()->json([
                'success' => false,
                'message' => 'You don\'t have any naikans please create first',
                'user' => $authUser
            ], 400);
        } else {
            return response()->json([
                'success' => true,
                'naikans' => $naikans,
                'user' => $authUser
            ], 200);
        }
    }

    /* ------------- Store Naikans ----------- */
    public function store(Request $request)
    {
        $request->validate([
            'R' => 'required',
            'G' => 'required',
            'TD' => 'required'
        ]);
        $authUser = Auth::user();
        $naikan = '';
        $input = $request->all();
        $input['user_id'] = $authUser['id'];
        $isNaikanToday = Naikan::where([
            ['user_id', '=', $authUser['id']],
            ['created_at', '>=', Carbon::today()]
        ]);
        $isUpdate = false;
        if ($isNaikanToday->exists()) {
            $updateIfExists = $isNaikanToday->update([
                'R' => $request->R,
                'G' => $request->G,
                'TD' => $request->TD
            ]);
            if ($updateIfExists) {
                $isUpdate = true;
            }
            $naikan = $isNaikanToday->first();
        } else {
            $naikan = Naikan::create($input);
        }
        return response()->json([
            'success' => true,
            'isUpdate' => $isUpdate,
            'naikan' => $naikan,
            'user' => $authUser
        ], 200);
    }

    /* ------------- Show Naikan by ID ----------- */
    public function show(Naikan $naikan)
    {
        $isNaikanExists = Naikan::where('id', $naikan['id'])->exists();
        if ($isNaikanExists) {
            return response()->json([
                'success' => true,
                'naikans' => $naikan
            ], 200);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Naikan not available'
            ], 400);
        }
    }

    /* ------------- Update Naikan by ID ----------- */
    public function update(Request $request, Naikan $naikan)
    {
        $request->validate([
            'R' => 'required',
            'G' => 'required',
            'TD' => 'required',
        ]);
        $authUser = Auth::user();
        $input = $request->post();
        $input['user_id'] = $authUser['id'];
        $naikan->fill($input)->save();

        return response()->json([
            'success' => true,
            'naikans' => $naikan
        ], 200);
    }

    /* ------------- Delete Naikan by ID ----------- */
    public function destroy(Naikan $naikan)
    {
        $naikan->delete();
        return response()->json([
            'success' => true,
            'message' => 'Naikan removed'
        ], 200);
    }
}