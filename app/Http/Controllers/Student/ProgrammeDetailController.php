<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ProgrammeDetails;
use Exception;
use Illuminate\Support\Facades\Log;
use App\Models\StudentActivities;
use App\Models\WatchDetail;
use App\Rules\CategoryProgrammeChecker;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class ProgrammeDetailController extends Controller
{
    protected $student_id;

    public function __construct()
    {
        $this->student_id = Auth::guard('student-api')->user()->id;
    }

    public function index ($programme, $category = "all")
    {
        $rules = [
            'programme' => 'required|in:webinar,event',
            'category' => $category != "all" ? new CategoryProgrammeChecker($programme) : 'nullable',
        ];
        

        $validator = Validator::make([
            'programme' => $programme,
            'category' => $category,
        ], $rules);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'error' => $validator->errors()], 400);
        }

        try {

            $detail = ProgrammeDetails::whereHas('programmes', function ($query) use ($programme) {
                $query->where('prog_name', $programme);
            })->when($category != "all", function($query) use ($category) {
                $query->where('dtl_category', $category);
            })->orderBy('created_at', 'desc')->get();
        } catch (Exception $e) {
            Log::error('Failed to Fetch Webinar List : '.$e->getMessage());
            return response()->json(['success' => false, 'error' => 'Failed to fetch webinar list. Please try again.']);
        }

        return response()->json(['success' => true, 'data' => $detail]);   
    }
    
    public function find ($prog_dtl_id)
    {
        if (!$prog_detail = ProgrammeDetails::withCount('student_activities')->find($prog_dtl_id)) {
            return response()->json(['success' => false, 'error' => 'Couldn\'t find the programme detail']);
        }

        try {
            $prog_detail['watch_info'] = WatchDetail::whereHas('joined_activities', function($query) use ($prog_dtl_id) {
                $query->where('prog_dtl_id', $prog_dtl_id)->where('student_id', $this->student_id);
            })->first();

            $other = ProgrammeDetails::whereHas('programmes', function ($query) use ($prog_detail) {
                $query->where('prog_id', $prog_detail->prog_id);
            })->where('id', '!=', $prog_dtl_id)->where('dtl_category', $prog_detail->dtl_category)->get();
            
        } catch (Exception $e) {
            Log::error('Find Programme Detail by Id Issue : ['.$prog_dtl_id.'] '.$e->getMessage());
            return response()->json(['success' => false, 'error' => 'Failed to find programme detail by Id. Please try again.']);
        }
        return response()->json(['success' => true, 'data' => ['detail' => $prog_detail, 'other' => $other]]);
    }
}
