<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Students;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class ProfileController extends Controller
{
    protected $student_id;

    public function __construct()
    {
        $this->student_id = auth()->guard('student-api')->user()->id;  
    }

    public function change_password(Request $request)
    {
        $rules = [
            'old_password' => 'required|password',
            'new_password' => 'required|string|min:6|confirmed|different:old_password'
        ];

        $custom_messages = [
            'new_password.different' => 'The new password must be different from your current password'
        ];

        $validator = Validator::make($request->all(), $rules, $custom_messages);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'error' => $validator->errors()], 400);
        }

        DB::beginTransaction();
        try {
            $student = Students::find($this->student_id);
            $student->password = Hash::make($request->new_password);
            $student->save();

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Change Students Password Issue : ['.json_encode($this->student_id).'] '.$e->getMessage());
            return response()->json(['success' => false, 'error' => 'Failed to change student password. Please try again.']);
        }

        return response()->json(['success' => true, 'message' => 'Your password has been changed']);
    }

    public function update(Request $request)
    {
        $rules = [
            'first_name'   => 'required|string|max:255',
            'last_name'    => 'required|string|max:255',
            'birthday'     => 'required|date',
            'phone_number' => 'required|string',
            'grade'        => 'required|integer|min:7'
        ];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'error' => $validator->errors()], 400);
        }

        DB::beginTransaction();
        try {
            $user = Students::find($this->student_id);
            $user->first_name = $request->first_name;
            $user->last_name = $request->last_name;
            $user->birthday = $request->birthday;
            $user->phone_number = $request->phone_number;
            $user->grade = $request->grade;
            $user->save();

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Update Student Profile Issue : ['.json_encode($this->student_id).'] '.$e->getMessage());
            return response()->json(['success' => false, 'error' => 'Failed to update student profile. Please try again.']);
        }

        return response()->json(['success' => true, 'message' => 'Profile has been updated', 'data' => auth()->guard('student-api')->user()->fresh()]);
    }
}
