<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Rules\RolesChecking;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class StudentPairingController extends Controller
{
    public function pair_student(Request $request)
    {
        $rules = [
            'student.*' => 'required|exists:students,id',
            'user_id' => ['required', 'exists:users,id', new RolesChecking('mentor')],
        ];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'error' => $validator->errors()], 400);
        }

        DB::beginTransaction();
        try {

            $user = User::find($request->user_id);
            for ($i = 0 ; $i < count($request->student) ; $i++) {
                if ($user->students()->where('student_id', $request->student[$i])->first()) {
                    continue;
                }

                $user->students()->attach($request->student[$i], [
                    'created_at' => Carbon::now(), 
                    'updated_at' => Carbon::now(),
                    'status' => 1
                ]);
            }
            DB::commit();

        } catch (Exception $e) {
            Log::error("Pairing Mentee Issue : ".$e->getMessage());
            return response()->json(['success' => false, 'error' => 'Failed to pairing mentee']);
        }

        return response()->json(['success' => true, 'data' => $user->students]);
    }
}
