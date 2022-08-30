<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Competitions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Exception;
use App\Models\Students;
use Illuminate\Support\Facades\Log;

class CompetitionController extends Controller
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
            return response()->json(['success' => true, 'data' => $student->competitions]);
        }

        $competition = Competitions::where('student_id', $this->student_id)->orderBy('created_at', 'desc')->get();
        return response()->json(['success' => true, 'data' => $competition]);
    }

    public function delete($comp_id)
    {
        if (!$competition = Competitions::find($comp_id)) {
            return response()->json(['success' => false, 'error' => 'Id does not exist'], 400);
        }

        DB::beginTransaction();
        try {
            $competition->delete();
            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Delete Competition Issue : ['.json_encode($comp_id).'] '.$e->getMessage());
            return response()->json(['success' => false, 'error' => 'Failed to delete competition. Please try again.']);
        }

        return response()->json(['success' => true, 'message' => 'Competition has been deleted']);
    }

    public function update($comp_id, Request $request)
    {
        if (!$competition = Competitions::find($comp_id)) {
            return response()->json(['success' => false, 'error' => 'Id does not exist'], 400);
        }

        $rules = [
            'comp_name' => 'required|max:255',
            'participation_level' => 'required',
            'accomplishment' => 'required',
            'month' => 'nullable|string|max:2',
            'year' => 'nullable|string|max:4'
        ];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'error' => $validator->errors()], 400);
        }

        DB::beginTransaction();
        try {
            $competition->comp_name = $request->comp_name;
            $competition->participation_level = $request->participation_level;
            $competition->accomplishments = $request->accomplishment;
            $competition->month = $request->month;
            $competition->year = $request->year;
            $competition->save();

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Update Competition Issue : ['.json_encode($request->all()).'] '.$e->getMessage());
            return response()->json(['success' => false, 'error' => 'Failed to update competition. Please try again.']);
        }

        return response()->json(['success' => true, 'message' => 'Competition has been updated']);
    }

    public function store(Request $request)
    {
        $rules = [
            'comp_name' => 'required|max:255',
            'participation_level' => 'required',
            'accomplishment' => 'required',
            'month' => 'nullable|string|max:2',
            'year' => 'nullable|string|max:4'
        ];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'error' => $validator->errors()], 400);
        }

        DB::beginTransaction();
        try {
            $competition = new Competitions;
            $competition->student_id = $this->student_id;
            $competition->comp_name = $request->comp_name;
            $competition->participation_level = $request->participation_level;
            $competition->accomplishments = $request->accomplishment;
            $competition->month = $request->month;
            $competition->year = $request->year;
            $competition->save();

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Add Competition Issue : ['.json_encode($request->all()).'] '.$e->getMessage());
            return response()->json(['success' => false, 'error' => 'Failed to add competition. Please try again.']);
        }

        return response()->json(['success' => true, 'message' => 'Competition has been added']);
    }
}
