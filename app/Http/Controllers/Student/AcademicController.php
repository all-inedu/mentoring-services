<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\AcademicRecords;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Exception;
use Illuminate\Support\Facades\Log;
use App\Models\Students;

class AcademicController extends Controller
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
            return response()->json(['success' => true, 'data' => $student->academic_records]);
        }

        $academic = AcademicRecords::where('student_id', $this->student_id)->orderBy('school_subject', 'asc')->get();
        return response()->json(['success' => true, 'data' => $academic]);
    }

    public function delete($aca_id)
    {
        if (!$academic = AcademicRecords::find($aca_id)) {
            return response()->json(['success' => false, 'error' => 'Id does not exist'], 400);
        }

        DB::beginTransaction();
        try {
            $academic->delete();
            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Delete Academic Record Issue : ['.json_encode($aca_id).'] '.$e->getMessage());
            return response()->json(['success' => false, 'error' => 'Failed to delete academic record. Please try again.']);
        }

        return response()->json(['success' => true, 'message' => 'Record has been deleted']);
    }

    public function update($aca_id, Request $request)
    {
        if (!$academic = AcademicRecords::find($aca_id)) {
            return response()->json(['success' => false, 'error' => 'Id does not exist'], 400);
        }

        $rules = [
            'school_subject' => 'required|max:255',
            'score' => 'required|integer',
            'max_score' => 'required|integer'
        ];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'error' => $validator->errors()], 400);
        }

        DB::beginTransaction();
        try {
            $academic->school_subject = $request->school_subject;
            $academic->score = $request->score;
            $academic->max_score = $request->max_score;
            $academic->save();

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Update Academic Score Issue : ['.json_encode($request->all()).'] '.$e->getMessage());
            return response()->json(['success' => false, 'error' => 'Failed to update academic score. Please try again.']);
        }

        return response()->json(['success' => true, 'message' => 'Academic Score has been updated']);
    }

    public function store(Request $request)
    {
        $rules = [
            'school_subject' => 'required|max:255',
            'score' => 'required|integer',
            'max_score' => 'required|integer'
        ];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'error' => $validator->errors()], 400);
        }

        if ($request->max_score < $request->score) {
            return response()->json(['success' => false, 'error' => 'Max score must be higher than the score']);
        }

        DB::beginTransaction();
        try {
            $academic = new AcademicRecords;
            $academic->student_id = $this->student_id;
            $academic->school_subject = $request->school_subject;
            $academic->score = $request->score;
            $academic->max_score = $request->max_score;
            $academic->save();

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Add Academic Score Issue : ['.json_encode($request->all()).'] '.$e->getMessage());
            return response()->json(['success' => false, 'error' => 'Failed to add academic score. Please try again.']);
        }

        return response()->json(['success' => true, 'message' => 'Academic Score has been added']);
    }
}
