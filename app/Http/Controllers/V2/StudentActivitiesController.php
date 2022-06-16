<?php

namespace App\Http\Controllers\V2;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\Models\StudentActivities;

class StudentActivitiesController extends Controller
{
    
    public function index_by_student ($programme, $status, $recent = NULL, Request $request)
    {
        $rules = [
            'programme' => 'required|in:1-on-1-call,webinar,event',
            'status' => 'nullable|in:new,pending,upcoming,history'
        ];

        $validator = Validator::make([
            'programme' => $programme,
            'status' => $status
        ], $rules);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'error' => $validator->errors()], 400);
        }

        $using_status = $request->get('status') ? 1 : 0;
        $status = $request->get('status') != NULL ? $request->get('status') : false;
        
        $id = auth()->guard('student-api')->user()->id;

        $use_keyword = $request->get('keyword') ? 1 : 0;
        $keyword = $request->get('keyword') != NULL ? $request->get('keyword') : null;

        $activities = StudentActivities::with(['programmes', 'students', 'users', 'programme_details'])->
            whereHas('programmes', function($query) use ($programme) {
                $query->where('prog_name', $programme);
        })->when($use_keyword, function($query) use ($keyword, $programme) {
            $query->when($programme == "1-on-1-call", function ($q1) use ($keyword) {
                $q1->where(function($q2) use ($keyword) {
                    $q2->where(DB::raw("CONCAT(`module`, ' - ', `call_with`)"), 'like', '%'.$keyword.'%')->orWhereHas('users', function($q3) use ($keyword) {
                        $q3->where(DB::raw("CONCAT(`first_name`, ' ', `last_name`)"), 'like', '%'.$keyword.'%');
                    })->orWhereHas('students', function ($q) use ($keyword) {
                        $q->where(DB::raw("CONCAT(`first_name`, ' - ', `last_name`)"), 'like', '%'.$keyword.'%');
                    });
                });
            });
        })->whereHas('students', function ($q) use ($id) {
            $q->where('id', $id);
        })->when($using_status, function($query) use ($id, $status){
            $query->where('std_act_status', $status);
        })->orderBy('created_at', 'desc')->recent($recent, $this->ADMIN_LIST_PROGRAMME_VIEW_PER_PAGE);

        return response()->json(['success' => true, 'data' => $activities]);
    }
}
