<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Interests;
use App\Models\Students;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Exception;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class InterestController extends Controller
{
    protected $student_id;

    public function __construct()
    {
        $this->student_id = auth()->guard('student-api')->user()->id;        
    }
    
    public function index(Request $request)
    {
        $email = $request->get('mail') != null ? $request->get('mail') : null;
        if ($email != null) {
            $student = Students::where('email', $email)->first();
            return response()->json(['success' => true, 'data' => $student->interests]);
        }

        $interest = Interests::where('student_id', $this->student_id)->orderBy('career_major_name', 'asc')->get();
        return response()->json(['success' => true, 'data' => $interest]);
    }

    public function delete($interest_id)
    {
        if (!$interest = Interests::find($interest_id)) {
            return response()->json(['success' => false, 'error' => 'Id does not exist'], 400);
        }

        DB::beginTransaction();
        try {
            $interest->delete();
            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Delete Interest Issue : ['.json_encode($interest_id).'] '.$e->getMessage());
            return response()->json(['success' => false, 'error' => 'Failed to delete interest. Please try again.']);
        }

        return response()->json(['success' => true, 'message' => 'Interest has been deleted']);
    }

    public function update($interest_id, Request $request)
    {
        if (!$interest = Interests::find($interest_id)) {
            return response()->json(['success' => false, 'error' => 'Id does not exist'], 400);
        }

        $rules = [
            'exists' => 'boolean',
            'career_major_name' => 'required_if:exists,==,true|nullable|unique:interests,career_major_name|exists:mysql_internship.tb_specialization,spec_name,'.$interest_id.',spec_status,1',
            'career_major_other' => 'required_if:exists,==,false|nullable|unique:interests,career_major_name,'.$interest_id
        ];

        $custom_messages = [
            'career_major_name.unique' => 'The career or major name has already been taken.',
            'career_major_name.exists' => 'Selected career or major name is invalid.',
            'career_major_name.alpha' => 'The career or major name must only contain letters.',
            'career_major_other.unique' => 'The career or major name has already been taken.',
            'career_major_other.exists' => 'Selected career or major name is invalid.',
            'career_major_other.alpha' => 'The career or major name must only contain letters.',
        ];

        $validator = Validator::make($request->all(), $rules, $custom_messages);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'error' => $validator->errors()], 400);
        }

        DB::beginTransaction();
        try {
            $interest->career_major_name = $request->exists == true ? $request->career_major_name : $request->career_major_other;;
            $interest->save();

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Edit Interest Issue : ['.json_encode($request->all()).'] '.$e->getMessage());
            return response()->json(['success' => false, 'error' => 'Failed to update interest. Please try again.']);
        }

        return response()->json(['success' => true, 'message' => 'Interest has been updated']);
    }

    public function store(Request $request)
    {
        $rules = [
            'exists' => 'boolean',
            'career_major_name' => 'required_if:exists,==,true|nullable|unique:interests,career_major_name|exists:mysql_internship.tb_specialization,spec_name,spec_status,1',
            'career_major_other' => 'required_if:exists,==,false|nullable|unique:interests,career_major_name'
        ];

        $custom_messages = [
            'career_major_name.unique' => 'The career or major name has already been taken.',
            'career_major_name.exists' => 'Selected career or major name is invalid.',
            'career_major_name.alpha' => 'The career or major name must only contain letters.',
            'career_major_other.unique' => 'The career or major name has already been taken.',
            'career_major_other.exists' => 'Selected career or major name is invalid.',
            'career_major_other.alpha' => 'The career or major name must only contain letters.',
        ];

        $validator = Validator::make($request->all(), $rules, $custom_messages);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'error' => $validator->errors()], 400);
        }

        DB::beginTransaction();
        try {
            $interest = new Interests;
            $interest->student_id = $this->student_id;
            $interest->career_major_name = $request->exists == true ? $request->career_major_name : $request->career_major_other;
            $interest->save();

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Add Interest Issue : ['.json_encode($request->all()).'] '.$e->getMessage());
            return response()->json(['success' => false, 'error' => 'Failed to add interest. Please try again.']);
        }

        return response()->json(['success' => true, 'message' => 'Interest has been added']);
    }
}
