<?php

namespace App\Http\Controllers\V2;

use App\Http\Controllers\Controller;
use App\Http\Controllers\HelperController;
use App\Models\GroupMeeting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\Models\StudentActivities;
use App\Providers\RouteServiceProvider;
use App\Models\GroupProject;
use Illuminate\Support\Facades\Auth;
use App\Rules\RolesChecking;
use App\Models\Programmes;
use App\Models\Students;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\User\MeetingMinuteController;
use App\Models\MeetingMinutes;
use Illuminate\Support\Facades\Log;
use Exception;

class StudentActivitiesController extends Controller
{

    protected $student_id;
    protected $STUDENT_MEETING_VIEW_PER_PAGE;

    public function __construct()
    {
        $this->student_id = Auth::guard('student-api')->check() ? auth()->guard('student-api')->user()->id : NULL;
        $this->user_id = Auth::guard('api')->check() ? Auth::guard('api')->user()->id : NULL;
        $this->STUDENT_MEETING_VIEW_PER_PAGE = RouteServiceProvider::STUDENT_MEETING_VIEW_PER_PAGE;
        $this->ADMIN_LIST_PROGRAMME_VIEW_PER_PAGE = RouteServiceProvider::ADMIN_LIST_PROGRAMME_VIEW_PER_PAGE;
    }

    public function store(Request $request)
    {
        /** list of programmes
         * 1. 1-on-1-call
         * 2. contact-mentor
         * 3. webinar
         * 4. event
         * 5. subscription
         */
        $programme_id = 1; //! for 1-on-1 call
        $student_id = $request->student_id;

        $rules = [
            'student_id' => 'required|exists:students,id',
            'handled_by' => ['nullable', new RolesChecking('admin')],
            'location_link' => 'nullable|url',
            'location_pw' => 'nullable',
            'call_with' => 'required_if:activities,1-on-1-call|in:mentor,alumni,editor',
            'module' => 'required_if:activities,1-on-1-call|in:life skills,career exploration,university admission,life at university',
            'call_date' => ['required_if:activities,1-on-1-call|date'/*, new CheckAvailabilityUserSchedule($request->user_id)*/],
            'created_by' => 'required|in:mentor,editor,alumni'
        ];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'error' => $validator->errors()], 400);
        }

        // $request_date = $request->call_date;
        // $hour_before = date('Y-m-d H:i', strtotime("-1 hour", strtotime($request_date)));
        // $hour_after = date('Y-m-d H:i', strtotime("+1 hour", strtotime($request_date)));

        // if ($activity = StudentActivities::where(function($query) use ($student_id){
        //     $query->where('student_id', $student_id)->orWhere('user_id', $this->user_id);
        // })
        // ->where('prog_id', $programme_id)->where('call_status', 'waiting')
        // ->where(function($query) use ($hour_before, $hour_after, $request_date) {
        //     $query->whereBetween('call_date', [$hour_before, $request_date])
        //     ->orWhereBetween('call_date', [$request_date, $hour_after]);
        // })->first()){

        //     // validate if request call_date not clash with the other schedule
        //     // will check 1 hour before and 1 hour after
        //     if (date('Y-m-d H:i', strtotime("+1 hour", strtotime($activity->call_date))) > $request->call_date) {
        //         $custom_msg = ($activity->user_id == $this->user_id) ? " with you" : "";
        //         return response()->json([
        //             'success' => false, 
        //             'error' => $activity->mt_confirm_status == "confirmed" ?  
        //                 'You already make an appoinment at '.date('l, d M Y H:i', strtotime($activity->call_date)) :
        //                 'Your student/mentee already has schedule'.$custom_msg.' at '.date('l, d M Y H:i', strtotime($request->call_date))
        //         ]);
        //     }
        // }
        
        DB::beginTransaction();
        try {

            $request_data = [
                'prog_id' => $programme_id, 
                'student_id' => $request->student_id,
                'user_id' => $this->user_id,
                'std_act_status' => 'waiting',
                'mt_confirm_status' => 'confirmed',
                'handled_by' => NULL, //! for now set to null
                'location_link' => $request->location_link,
                'location_pw' => $request->location_pw,
                'prog_dtl_id' => NULL,
                'call_with' => $request->call_with,
                'module' => $request->module,
                'call_date' => $request->call_date,
                'call_status' => 'waiting',
                'created_by' => $request->created_by
            ];

            //select programmes 
            $programmes = Programmes::find($programme_id);
            $prog_price = $programmes->prog_price; //price that will be inserted into transaction

            // check if the student is the internal student or external
            $student = Students::find($request->student_id);
            $total_amount = ($student->imported_id != NULL) ? 0 : $prog_price; //set to 0 if student is internal student

            $activities = StudentActivities::create($request_data);
            $response['activities'] = $activities;
            $st_act_id = $activities->id;

            $data = [
                'student_id' => $request->student_id,
                'st_act_id'   => $st_act_id,
                'amount'       => $prog_price,
                'total_amount' => $total_amount,
                'status'       => 'paid' //! sementara langsung paid, ke depannya akan diubah dari pending dlu
            ];

            $transaction = new TransactionController;
            $response['transaction'] = $transaction->store($data);

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Create Student Activities from Mentor Issue : ['.json_encode($request->all()).'] '.$e->getMessage());
            return response()->json(['success' => false, 'error' => 'Failed to create 1 on 1 call. Please try again.']);
        }

        return response()->json(['success' => true, 'message' => '1 on 1 Call has been made', 'data' => $response['activities']]);
    }

    public function index($programme, $status = NULL, $recent = NULL, Request $request)
    {
        $meeting_minutes = $request->get('meeting-minutes') == "yes" ? $request->get('meeting-minutes') : null;

        $rules = [
            'programme' => 'required|in:1-on-1-call,webinar,event',
            'status' => 'nullable|in:new,pending,upcoming,history',
        ];

        $validator = Validator::make(['programme' => $programme, 'status' => $status], $rules);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'error' => $validator->errors()], 400);
        }

        // kondisi buat nampilin data khusus yg di dashboard mentor
        if (($recent != NULL) && ($status == "upcoming")) {
            $data['upcoming'] = $this->get_index($programme, $status, $recent, null);
            $data['latest_meeting'] = $this->get_index($programme, $status, $recent, "yes")->where('meeting_minute', 0)->unique('id')->values();
        } else {
            $data = $this->get_index($programme, $status, $recent, $meeting_minutes);
        }
        

        return response()->json(['success' => true, 'data' => $data]);
    }

    public function get_index($programme, $status, $recent, $meeting_minutes)
    {
        $activities = StudentActivities::with(['students', 'users'])->withCount('meeting_minutes as meeting_minute')->where('user_id', $this->user_id)
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
        ->when($status == 'history', function($query) use ($meeting_minutes) {
            $query->when($meeting_minutes == NULL, function ($query1) {
                $query1->where(function ($query2) {
                    $query2->where('call_status', 'finished')->orWhere('call_status', 'canceled')->orWhere('call_status', 'rejected');
                });
            }, function($query1) {
                $query1->where('call_status', 'finished');
            })
            ->orderBy('call_status', 'desc')
            ->orderBy('call_date', 'desc');
        })
        ->whereHas('programmes', function($query) use ($programme) {
            $query->where('prog_name', $programme);
        })
        ->recent($recent, $this->ADMIN_LIST_PROGRAMME_VIEW_PER_PAGE);

        return $activities;
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
    
    public function index_by_student ($person, $programme, $status = NULL, $recent = NULL, Request $request)
    {
        $webinar_category = $request->get('category');
        $rules = [
            'person' => 'required|in:mentor,student',
            'programme' => 'required|in:1-on-1-call,webinar,event',
            'status' => 'nullable|in:new,pending,upcoming,history',
        ];
        
        if ($programme == "webinar") {
            $rules['category'] = $webinar_category != "" ? 'exists:programme_details,dtl_category' : '';
        }

        $validator = Validator::make(['person' => $person, 'programme' => $programme, 'status' => $status, 'category' => $webinar_category], $rules);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'error' => $validator->errors()], 400);
        }

        if (($person == "mentor") && (!$request->get('student'))) {
            return response()->json(['success' => false, 'error' => 'Couldn\'t find the students webinar history']);
        } else if (($person == "student") && ($request->get('student'))) {
            return response()->json(['success' => false, 'error' => 'No access']);
        }
        
        $student_id = $request->get('student') ? $request->get('student') : $this->student_id;

        // $using_status = $request->get('status') ? 1 : 0;
        // $status = $request->get('status') != NULL ? $request->get('status') : false;

        $use_keyword = $request->get('keyword') ? 1 : 0;
        $keyword = $request->get('keyword') != NULL ? $request->get('keyword') : null;

        $with = ['students', 'users'];
        if ($programme == "webinar") {
            array_push($with,"programme_details", "watch_detail");
        }

        $helper = new HelperController;
        
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
        })->whereHas('students', function ($q) use ($student_id) {
            $q->where('id', $student_id);
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
        })->get();

        return response()->json(['success' => true, 'data' => $helper->paginate($activities)->appends(array('student' => $student_id))]);
    }
}
