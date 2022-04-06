<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\StudentMentors;
use Database\Seeders\UserSeeder;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Validator;
use App\Models\User;
use App\Rules\RolesChecking;
use Illuminate\Support\Facades\Auth;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Students;

class StudentMentorController extends Controller
{

    private $assigned_role_name;

    public function __construct()
    {
        $this->assigned_role_name = 'mentor';
    }

    public function find($mentor_id)
    {
        $mentor = User::whereHas('roles', function ($query) {
            $query->where('role_name', '!=', 'admin');
        })->where('id', $mentor_id)->first();
        if (!$mentor) {
            return response()->json(['success' => false, 'error' => 'Couldn\'t find mentor']);
        }
        
        return response()->json(['success' => true, 'data' => $mentor->user_schedules]);
    }

    public function list(Request $request)
    {
        $rules = [
            'mail' => 'required|exists:students,email'
        ];

        $validator = Validator::make(['mail' => $request->get('mail')], $rules);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'error' => $validator->errors()], 400);
        }

        $student = Students::where('email', $request->get('mail'))->first();
        return response()->json(['success' => true, 'data' => $student->users]);
    }

    public function store(Request $request)
    {

        $rules = [
            'student_id' => 'required|exists:students,id',
            'user_id.*' => ['required', 'distinct', new RolesChecking('mentor')]
        ];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'error' => $validator->errors()], 400);
        }

        DB::beginTransaction();
        try {
            foreach ($request->user_id as $key => $value) {
                $assigned_mentor = new StudentMentors;
                $assigned_mentor->student_id = $request->student_id;
                $assigned_mentor->user_id = $value;
                $assigned_mentor->save();

                $data[] = $assigned_mentor;
            }
            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Assigning Mentor Issue : ['.json_encode($data).'] '.$e->getMessage());
            return response()->json(['success' => false, 'error' => 'Failed to assigning mentor. Please try again.']);
        }

        return response()->json(['success' => true, 'message' => 'Mentor has been assigned', 'data' => $data]);
    }
}
