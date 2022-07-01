<?php

namespace App\Http\Controllers\V2;

use App\Http\Controllers\Controller;
use App\Models\GroupMeeting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\Models\StudentActivities;
use App\Providers\RouteServiceProvider;
use App\Models\GroupProject;
use Illuminate\Support\Facades\Auth;

class StudentActivitiesController extends Controller
{

    protected $student_id;
    protected $STUDENT_MEETING_VIEW_PER_PAGE;

    public function __construct()
    {
        $this->student_id = auth()->guard('student-api') != "" ? auth()->guard('student-api')->user()->id : NULL;
        $this->user_id = Auth::guard('api') != "" ? Auth::guard('api')->user()->id : NULL;
        $this->STUDENT_MEETING_VIEW_PER_PAGE = RouteServiceProvider::STUDENT_MEETING_VIEW_PER_PAGE;
        $this->ADMIN_LIST_PROGRAMME_VIEW_PER_PAGE = RouteServiceProvider::ADMIN_LIST_PROGRAMME_VIEW_PER_PAGE;
    }

    public function store()
    {
        
    }

    public function index($programme, $status = NULL, $recent = NULL, Request $request)
    {
        $rules = [
            'programme' => 'required|in:1-on-1-call,webinar,event',
            'status' => 'nullable|in:new,pending,upcoming,history',
        ];

        $validator = Validator::make(['programme' => $programme, 'status' => $status], $rules);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'error' => $validator->errors()], 400);
        }

        $activities = StudentActivities::with(['students', 'users'])->where('user_id', $this->user_id)
                    ->when($status == 'new', function($query) {
                        $query->where('std_act_status', 'confirmed')->where('mt_confirm_status', 'waiting')->where('call_status', 'waiting')
                        ->orderBy('call_status', 'desc')
                        ->orderBy('call_date', 'asc');
                    })
                    ->when($status == 'pending', function($query) {
                        $query->where('std_act_status', 'waiting')->where('mt_confirm_status', 'confirmed')->where('call_status', 'waiting')
                        ->orderBy('call_status', 'desc')
                        ->orderBy('call_date', 'asc');
                    })
                    ->when($status == 'upcoming', function($query) {
                        $query->where('std_act_status', 'confirmed')->where('mt_confirm_status', 'confirmed')->where('call_status', 'waiting')
                        ->orderBy('call_status', 'desc')
                        ->orderBy('call_date', 'asc');
                    })
                    ->when($status == 'history', function($query) {
                        $query->where(function ($query1) {
                            $query1->where('call_status', 'finished')->orWhere('call_status', 'canceled')->orWhere('call_status', 'rejected');
                        })
                        ->orderBy('call_status', 'desc')
                        ->orderBy('call_date', 'desc');
                    })
                    ->recent($recent, $this->ADMIN_LIST_PROGRAMME_VIEW_PER_PAGE);

        return response()->json(['success' => true, 'data' => $activities]);
    }

    public function index_student_count()
    {
        // 1-on-1 call
        // new request
        $data['personal']['request'] = StudentActivities::whereHas('programmes', function($query) {
            $query->where('prog_name', '1-on-1-call');
        })->whereHas('students', function($query) {
            $query->where('id', $this->student_id);
        })->where('std_act_status', 'waiting')->where('mt_confirm_status', 'confirmed')->where('call_status', 'waiting')->count();

        // pending
        $data['personal']['pending'] = StudentActivities::whereHas('programmes', function($query) {
            $query->where('prog_name', '1-on-1-call');
        })->whereHas('students', function($query) {
            $query->where('id', $this->student_id);
        })->where('std_act_status', 'confirmed')->where('mt_confirm_status', 'waiting')->where('call_status', 'waiting')->count();

        // upcoming
        $data['personal']['upcoming'] = StudentActivities::whereHas('programmes', function($query) {
            $query->where('prog_name', '1-on-1-call');
        })->whereHas('students', function($query) {
            $query->where('id', $this->student_id);
        })->where('std_act_status', 'confirmed')->where('mt_confirm_status', 'confirmed')->where('call_status', 'waiting')->count();

        // history (finished, canceled, rejected)
        $data['personal']['history'] = StudentActivities::whereHas('programmes', function($query) {
            $query->where('prog_name', '1-on-1-call');
        })->whereHas('students', function($query) {
            $query->where('id', $this->student_id);
        })->where(function ($query) {
            $query->where('call_status', 'finished')->orWhere('call_status', 'canceled')->orWhere('call_status', 'rejected');
        })->count();

        //! tambahin status tidak include yg cancel
        // group meeting
        // new request
        $data['group_m']['upcoming'] = GroupMeeting::whereHas('group_project', function($query) {
            $query->whereHas('group_participant', function($query1) {
                $query1->where('student_id', $this->student_id);
            });
        })->where('group_meetings.status', 0)->count();
        $data['group_m']['history'] = GroupMeeting::whereHas('group_project', function($query) {
            $query->whereHas('group_participant', function($query1) {
                $query1->where('student_id', $this->student_id);
            });
        })->where(function ($query) {
            $query->where('group_meetings.status', 1)->orWhere('group_meetings.status', 2);
        })->count();

        // group project
        // new request
        $data['group']['request'] = GroupProject::whereHas('group_participant', function ($query) {
            $query->where('student_id', $this->student_id)->where('participants.status', 0);
        })->count();

        // in progress
        $data['group']['upcoming'] = GroupProject::whereHas('group_participant', function ($query) {
            $query->where('student_id', $this->student_id)->where('participants.status', 1);
        })->where('status', 'in progress')->count();

        // history
        $data['group']['history'] = GroupProject::whereHas('group_participant', function ($query) {
            $query->where('student_id', $this->student_id)->where('participants.status', 1);
        })->where('status', 'completed')->count();


        return response()->json([
            'success' => true,
            'data' => $data
        ]);
    }
    
    public function index_by_student ($programme, $status = NULL, $recent = NULL, Request $request)
    {
        $webinar_category = $request->get('category');
        $rules = [
            'programme' => 'required|in:1-on-1-call,webinar,event',
            'status' => 'nullable|in:new,pending,upcoming,history',
        ];
        
        if ($programme == "webinar") {
            $rules['category'] = $webinar_category != "" ? 'exists:programme_details,dtl_category' : '';
        }

        $validator = Validator::make(['programme' => $programme, 'status' => $status, 'category' => $webinar_category], $rules);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'error' => $validator->errors()], 400);
        }

        // $using_status = $request->get('status') ? 1 : 0;
        // $status = $request->get('status') != NULL ? $request->get('status') : false;

        $use_keyword = $request->get('keyword') ? 1 : 0;
        $keyword = $request->get('keyword') != NULL ? $request->get('keyword') : null;

        $with = ['students', 'users'];
        if ($programme == "webinar") {
            array_push($with,"programme_details", "watch_detail");
        }
        
        $activities = StudentActivities::with($with)->whereHas('programmes', function($query) use ($programme) {
                $query->where('prog_name', $programme);
        })->when($webinar_category != NULL, function ($query) use ($webinar_category) {
            $query->whereHas('programme_details', function ($query1) use ($webinar_category) {
                $query1->where('dtl_category', $webinar_category);
            });
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
        })->whereHas('students', function ($q) {
            $q->where('id', $this->student_id);
        })->when($status == 'new', function ($q) {
            $q->where('std_act_status', 'waiting')->where('mt_confirm_status', 'confirmed')->where('call_status', 'waiting')
            ->orderBy('call_status', 'desc')
            ->orderBy('call_date', 'asc');
        })->when($status == 'pending', function ($q) {
            $q->where('std_act_status', 'confirmed')->where('mt_confirm_status', 'waiting')->where('call_status', 'waiting')
            ->orderBy('call_status', 'desc')
            ->orderBy('call_date', 'asc');
        })->when($status == 'upcoming', function ($q) {
            $q->where('std_act_status', 'confirmed')->where('mt_confirm_status', 'confirmed')->where('call_status', 'waiting')
            ->orderBy('call_status', 'desc')
            ->orderBy('call_date', 'asc');
        })->when($status == "history", function ($q) {
            $q->where(function($q1) { 
                $q1->where('call_status', 'finished')->orWhere('call_status', 'canceled')->orWhere('call_status', 'rejected');
            })
            // where(function ($q1) { // history dari call status yg berhasil
            //     $q1->where('std_act_status', 'confirmed')->where('mt_confirm_status', 'confirmed')->where('call_status', 'finished');
            // })->orWhere(function ($q1) { // history dari call status yg cancel 
            //     $q1->where('std_act_status', 'cancel')->where('mt_confirm_status', 'confirmed')->where('call_status', 'canceled');
            // })->orWhere(function ($q1) {
            //     $q1->where('std_act_status', 'confirmed')->where('mt_confirm_status', 'cancel')->where('call_status', 'canceled');
            // })
            ->orderBy('call_status', 'desc')
            ->orderBy('call_date', 'desc');
        })->recent($recent, $this->STUDENT_MEETING_VIEW_PER_PAGE);

        return response()->json(['success' => true, 'data' => $activities]);
    }
}
