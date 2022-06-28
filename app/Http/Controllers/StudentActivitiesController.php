<?php

namespace App\Http\Controllers;

use App\Models\Programmes;
use App\Models\StudentActivities;
use Illuminate\Http\Request;
use App\Rules\RolesChecking;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Exception;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\TransactionController;
use App\Models\Students;
use App\Models\User;
use App\Providers\RouteServiceProvider;
use App\Rules\CheckAvailabilityUserSchedule;
use App\Rules\PersonalMeetingChecker;
use Illuminate\Support\Facades\Auth;
use App\Http\Traits\CreateActivitiesTrait;

class StudentActivitiesController extends Controller
{
    use CreateActivitiesTrait;
    protected $ADMIN_LIST_PROGRAMME_VIEW_PER_PAGE;

    public function __construct()
    {
        $this->ADMIN_LIST_PROGRAMME_VIEW_PER_PAGE = RouteServiceProvider::ADMIN_LIST_PROGRAMME_VIEW_PER_PAGE;
    }

    public function watch_time($std_act_id, Request $request)
    {
        if (!$student_activities = StudentActivities::find($std_act_id)) {
            return response()->json(['success' => false, 'error' => 'Couldn\'t find the activities']);
        }

        $rules = [
            'current_time' => 'required|integer' 
        ];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'error' => $validator->errors()], 400);
        }

        DB::beginTransaction();
        try {

            $old_current_time = $student_activities->watch_detail->current_time;

            if ($old_current_time < $request->current_time)  
                $student_activities->watch_detail()->update(['current_time' => $request->current_time]);

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Failed to Save Watch Time : '.$e->getMessage());
            return response()->json(['success' => false, 'error' => 'Failed to save watch time. Please try again.']);
        }

        return response()->json(['success' => true, 'message' => 'Watch time has updated', 'data' => $student_activities->watch_detail]);
    }

    public function set_meeting(Request $request)
    {
        $rules = [
            'id' => 'required|exists:student_activities,id',
            'link' => 'required|url'
        ];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'error' => $validator->errors()], 400);
        }

        $activities = StudentActivities::find($request->id);
        if ($activities->std_act_status == "waiting") {
            return response()->json(['success' => false, 'error' => 'The student has not confirmed the payment or please contact administrator for further information']);
        }

        DB::beginTransaction();
        try {
            
            $activities->location_link = $request->link;
            $activities->save();
            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Failed to set location link : '.$e->getMessage());
            return response()->json(['success' => false, 'error' => 'Failed to set location link. Please try again.']);
        }

        return response()->json(['success' => true, 'message' => 'Meeting location has been successfully arranged']);
    }
    
    public function index($programme, $recent = NULL, Request $request)
    {

        $student_email = $request->get('mail') != NULL ? $request->get('mail') : null;
        $is_student = $request->get('mail') ? true : false;

        //
        $user_id = $request->get('id') != NULL ? $request->get('id') : null;
        $find_detail = User::where('id', $user_id)->count() > 0 ? true : false;

        $use_keyword = $request->get('keyword') != NULL ? 1 : 0;
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
            })->when($is_student, function($query) use ($student_email) {
                $query->whereHas('students', function ($q) use ($student_email) {
                    $q->where('email', $student_email);
                });
            })->when($find_detail, function($query) use ($user_id) {
                $query->where('user_id', $user_id);
            })->orderBy('created_at', 'desc')->recent($recent, $this->ADMIN_LIST_PROGRAMME_VIEW_PER_PAGE);

        return response()->json(['success' => true, 'data' => $activities]);
    }

    public function index_by_student($programme, $recent = NULL, Request $request)
    {
        $rules = [
            'programme' => 'required|in:1-on-1-call,webinar,event',
            'status' => 'nullable|in:waiting,confirmed'
        ];

        $validator = Validator::make([
            'programme' => $programme,
            'status' => $request->get('status')
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

    public function index_by_auth($programme, $recent = NULL, Request $request)
    {
        $rules = [
            'programme' => 'required|in:1-on-1-call,webinar,event',
            'status' => 'nullable|in:waiting,confirmed'
        ];

        $validator = Validator::make([
            'programme' => $programme,
            'status' => $request->get('status')
        ], $rules);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'error' => $validator->errors()], 400);
        }

        $using_status = $request->get('status') ? 1 : 0;
        $status = $request->get('status') != NULL ? $request->get('status') : false;
        
        $id = auth()->guard('api')->user()->id;
        $student_email = $request->get('mail') != NULL ? $request->get('mail') : null;
        $is_student = $request->get('mail') ? true : false;

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
        })->when($is_student, function($query) use ($student_email) {
            $query->whereHas('students', function ($q) use ($student_email) {
                $q->where('email', $student_email);
            });
        })->when($using_status, function($query) use ($id, $status){
            $query->where('std_act_status', $status);
        })->where('user_id', $id)->orderBy('created_at', 'desc')->recent($recent, $this->ADMIN_LIST_PROGRAMME_VIEW_PER_PAGE);

        return response()->json(['success' => true, 'data' => $activities]);
    }

    public function store (Request $request)
    {
        /** list of programmes
         * 1. 1-on-1-call
         * 2. contact-mentor
         * 3. webinar
         * 4. event
         * 5. subscription
         */

        $rules = [
            'prog_id' => 'required|exists:programmes,id',  
            'student_id' => 'required|exists:students,id',
            'user_id' => ['nullable', new RolesChecking($request->call_with)],
            // 'std_act_status' => 'required|in:waiting,confirmed',
            'handled_by' => ['required', new RolesChecking('admin')],
            'location_link' => 'nullable',
            'prog_dtl_id'=> 'nullable|exists:programme_details,id',
            'call_with' => 'required|in:mentor,alumni,editor',
            'module' => 'required|in:life skills,career exploration,university admission,life university',
            'call_date' => ['required', new CheckAvailabilityUserSchedule($request->user_id)]
        ];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'error' => $validator->errors()], 400);
        }

        if (StudentActivities::where('student_id', $request->student_id)->where('call_date', $request->call_date)->where('call_status', 'in progress')->first()) {
            return response()->json(['success' => false, 'error' => 'You already make an appoinment at '.date('l, d M Y H:i', strtotime($request->call_date))]);
        }

        DB::beginTransaction();
        try {
            //select programmes 
            $programmes = Programmes::find($request->prog_id);
            $prog_price = $programmes->prog_price; //price that will be inserted into transaction

            // check if the student is the internal student or external
            $student = Students::find($request->student_id);
            $total_amount = ($student->imported_id != NULL) ? 0 : $prog_price; //set to 0 if student is internal student

            $activities = new StudentActivities;
            $activities->prog_id = $request->prog_id;
            $activities->student_id = $request->student_id;
            $activities->user_id = $request->user_id;
            $activities->std_act_status = 'confirmed';
            $activities->mt_confirm_status = 'waiting';
            $activities->handled_by = $request->handled_by;
            $activities->location_link = $request->location_link;
            $activities->prog_dtl_id = $request->prog_dtl_id;
            $activities->call_with = $request->call_with;
            $activities->module = $request->module;
            $activities->call_date = $request->call_date;
            $activities->save();
            $response['activities'] = $activities;
            $st_act_id = $activities->id;

            $data = [
                'student_id' => $request->student_id,
                'st_act_id'   => $st_act_id,
                'amount'       => $prog_price,
                'total_amount' => $total_amount,
                'status'       => 'pending'
            ];

            $transaction = new TransactionController;
            $response['transaction'] = $transaction->store($data);

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Create Student Activities Issue : ['.json_encode($request->all()).'] '.$e->getMessage());
            return response()->json(['success' => false, 'error' => 'Failed to create student activities. Please try again.']);
        }

        return response()->json(['success' => true, 'message' => 'Activities has been created', 'data' => $response]);
    }

    public function store_by_student ($activities, Request $request)
    {
        $student_id = Auth::guard('student-api')->user()->id;
        $rules = [
            'activities' => 'nullable|in:1-on-1-call,webinar,event',
            // 'prog_id' => 'required|exists:programmes,id',  
            // 'student_id' => 'required|exists:students,id',
            'user_id' => ['nullable', new RolesChecking($request->call_with)],
            // 'std_act_status' => 'required|in:waiting,confirmed',
            'handled_by' => ['nullable', new RolesChecking('admin')],
            'location_link' => 'nullable|url',
            'location_pw' => 'nullable',
            'prog_dtl_id'=> 'nullable|required_if:activities,webinar,event|exists:programme_details,id',
            'call_with' => 'required_if:activities,1-on-1-call|in:mentor,alumni,editor',
            'module' => 'required_if:activities,1-on-1-call|in:life skills,career exploration,university admission,life at university',
            'call_date' => ['required_if:activities,1-on-1-call'/*, new CheckAvailabilityUserSchedule($request->user_id)*/]
        ];

        $validator = Validator::make($request->all() + ['activities' => $activities], $rules);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'error' => $validator->errors()], 400);
        }

        switch ($activities) {
            case "1-on-1-call":
                $programme_id = 1; //! hardcode for now
                if (StudentActivities::where('student_id', $student_id)->where('prog_id', $programme_id)->where('call_date', $request->call_date)->first()) {
                    return response()->json(['success' => false, 'error' => 'You already make an appoinment at '.date('l, d M Y H:i', strtotime($request->call_date))]);
                }

                $request_data = [
                    'prog_id' => $programme_id, 
                    'student_id' => $student_id,
                    'user_id' => $request->user_id,
                    'std_act_status' => 'confirmed',
                    'mt_confirm_status' => 'waiting',
                    'handled_by' => NULL, //! for now set to null
                    'location_link' => $request->location_link,
                    'location_pw' => $request->location_pw,
                    'prog_dtl_id' => $request->prog_dtl_id,
                    'call_with' => $request->call_with,
                    'module' => $request->module,
                    'call_date' => $request->call_date,
                    'call_status' => 'waiting'
                ];
                break;

            case "event":
                $programme_id = 4; //! hardcode for now
                if (StudentActivities::where('student_id', $student_id)->where('prog_id', $programme_id)->where('prog_dtl_id', $request->prog_dtl_id)->first()) {
                    return response()->json(['success' => false, 'error' => 'You already joined the event']);
                }

                $request_data = [
                    'prog_id' => $programme_id,
                    'student_id' => $student_id,
                    'user_id' => NULL,
                    'std_act_status' => 'confirmed',
                    'mt_confirm_status' => NULL,
                    'handled_by' => NULL, //! for now set to null
                    'location_link' => $request->location_link,
                    'location_pw' => $request->location_pw,
                    'prog_dtl_id' => $request->prog_dtl_id,
                    'call_with' => NULL,
                    'module' => $request->module,
                    'call_date' => NULL,
                    'call_status' => NULL
                ];
                break;    
                
            case "webinar":
                $programme_id = 3; //! hardcode for now
                if (StudentActivities::where('student_id', $student_id)->where('prog_id', $programme_id)->where('prog_dtl_id', $request->prog_dtl_id)->first()) {
                    return response()->json(['success' => false, 'error' => 'You already joined the webinar']);
                }

                $request_data = [
                    'prog_id' => $programme_id,
                    'student_id' => $student_id,
                    'user_id' => NULL,
                    'std_act_status' => 'confirmed',
                    'mt_confirm_status' => NULL,
                    'handled_by' => NULL, //! for now set to null
                    'location_link' => NULL,
                    'location_pw' => NULL,
                    'prog_dtl_id' => $request->prog_dtl_id,
                    'call_with' => NULL,
                    'module' => $request->module,
                    'call_date' => NULL,
                    'call_status' => NULL
                ];
                break;
        }

        return $this->store_activities($request_data);
    }

    // public function store_by_student ($activities, Request $request)
    // {
    //     /** list of programmes
    //      * 1. 1-on-1-call
    //      * 2. contact-mentor
    //      * 3. webinar
    //      * 4. event
    //      * 5. subscription
    //      */

    //     $rules = [
    //         'activities' => 'nullable|in:1-on-1-call,webinar,event',
    //         'prog_id' => 'required|exists:programmes,id',  
    //         'student_id' => 'required|exists:students,id',
    //         'user_id' => ['nullable', new RolesChecking($request->call_with)],
    //         // 'std_act_status' => 'required|in:waiting,confirmed',
    //         'handled_by' => ['nullable', new RolesChecking('admin')],
    //         'location_link' => 'required|url',
    //         'location_pw' => 'required',
    //         'prog_dtl_id'=> 'nullable|required_if:activities,webinar,event|exists:programme_details,id',
    //         'call_with' => 'required_if:activities,1-on-1-call|in:mentor,alumni,editor',
    //         'module' => 'required|in:life skills,career exploration,university admission,life university',
    //         'call_date' => ['required_if:activities,1-on-1-call'/*, new CheckAvailabilityUserSchedule($request->user_id)*/]
    //     ];

    //     $validator = Validator::make($request->all() + ['activities' => $activities], $rules);
    //     if ($validator->fails()) {
    //         return response()->json(['success' => false, 'error' => $validator->errors()], 400);
    //     }

    //     if (StudentActivities::where('student_id', $request->student_id)->where('call_date', $request->call_date)->first()) {
    //         return response()->json(['success' => false, 'error' => 'You already make an appoinment at '.date('l, d M Y H:i', strtotime($request->call_date))]);
    //     }

    //     DB::beginTransaction();
    //     try {
    //         //select programmes 
    //         $programmes = Programmes::find($request->prog_id);
    //         $prog_price = $programmes->prog_price; //price that will be inserted into transaction

    //         //! bikin prog pricenya get dari programme detail kalau programme details id nya tidak null

    //         // check if the student is the internal student or external
    //         $student = Students::find($request->student_id);
    //         $total_amount = ($student->imported_id != NULL) ? 0 : $prog_price; //set to 0 if student is internal student

    //         switch ($activities) {
    //             case "1-on-1-call":
    //                 $activities = new StudentActivities;
    //                 $activities->prog_id = $request->prog_id;
    //                 $activities->student_id = $request->student_id;
    //                 $activities->user_id = $request->user_id;
    //                 $activities->std_act_status = 'confirmed';
    //                 $activities->mt_confirm_status = 'waiting';
    //                 $activities->handled_by = $request->handled_by;
    //                 $activities->location_link = $request->location_link;
    //                 $activities->prog_dtl_id = $request->prog_dtl_id;
    //                 $activities->call_with = $request->call_with;
    //                 $activities->module = $request->module;
    //                 $activities->call_date = $request->call_date;
    //                 $activities->call_status = "waiting";
    //                 $activities->save();

    //                 break;
                
    //             case "event":
    //                 $activities = new StudentActivities;
    //                 $activities->prog_id = $request->prog_id; //! di hardcode utk id programme event
    //                 $activities->student_id = $request->student_id;
    //                 $activities->user_id = null;
    //                 $activities->std_act_status = 'confirmed';
    //                 $activities->mt_confirm_status = null;
    //                 $activities->handled_by = $request->handled_by;
    //                 $activities->location_link = $request->location_link;
    //                 $activities->prog_dtl_id = $request->prog_dtl_id;
    //                 $activities->call_with = null;
    //                 $activities->module = $request->module;
    //                 $activities->call_date = null;
    //                 $activities->call_status = null;
    //                 $activities->save();
    //                 break;
    //         }

    //         $response['activities'] = $activities;
    //         $st_act_id = $activities->id;

    //         $data = [
    //             'student_id' => $request->student_id,
    //             'st_act_id'   => $st_act_id,
    //             'amount'       => $prog_price,
    //             'total_amount' => $total_amount,
    //             'status'       => 'paid' //! sementara langsung paid, ke depannya akan diubah dari pending dlu
    //         ];

    //         $transaction = new TransactionController;
    //         $response['transaction'] = $transaction->store($data);

    //         DB::commit();
    //     } catch (Exception $e) {
    //         DB::rollBack();
    //         Log::error('Create Student Activities Issue : ['.json_encode($request->all()).'] '.$e->getMessage());
    //         return response()->json(['success' => false, 'error' => 'Failed to create student activities. Please try again.']);
    //     }

    //     return response()->json(['success' => true, 'message' => 'Activities has been created', 'data' => $response]);
    // }

    public function confirmation_personal_meeting ($std_act_id, Request $request)
    {
        $rules = [
            'person' => 'required|in:student,mentor'
        ];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'error' => $validator->errors()], 400);
        }

        if (!$activities = StudentActivities::find($std_act_id)) {
            return response()->json(['success' => false, 'error' => 'Couldn\'t find the activities Id']);
        }

        DB::beginTransaction();
        try {

            switch ($request->person) {
                case "student":
                    $activities->std_act_status = 'confirmed';
                    break;

                case "mentor":
                    $activities->mt_confirm_status = 'confirmed';
                    break;
            }
            $activities->save();

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Confirmation ['.ucfirst($request->person).'] Meeting Issue : '.$e->getMessage());
            return response()->json(['success' => false, 'error' => 'Failed to confirm attendance. Please try again.']);
        }

        return response()->json(['success' => true, 'message' => 'You confirm to attend the meeting. Do not forget to check your schedule']);
    }

    public function cancel_reject_personal_meeting ($status, $std_act_id, Request $request)
    {
        $rules = [
            'person' => 'required|in:student,mentor',
            'status' => 'in:cancel,reject',
            'std_act_id' => [new PersonalMeetingChecker($request->person)]
        ];

        $validator = Validator::make($request->all() + ['status' => $status, 'std_act_id' => $std_act_id], $rules);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'error' => $validator->errors()], 400);
        }

        if (!$activities = StudentActivities::find($std_act_id)) {
            return response()->json(['success' => false, 'error' => 'Couldn\'t find the activities Id']);
        }

        switch ($activities->call_status) {
            case "finished":
                return response()->json(['success' => false, 'error' => 'You cannot '.strtolower($status).' the meeting that already finished']);
                break;

            case "canceled":
                return response()->json(['success' => false, 'error' => 'The meeting has already canceled']);
                break;

            case "rejected":
                return response()->json(['success' => false, 'error' => 'The meeting has already rejected']);
                break;
        }


        DB::beginTransaction();
        try {

            switch ($request->person) {
                case "student":
                    $activities->std_act_status = $status;
                    break;

                case "mentor":
                    $activities->mt_confirm_status = $status;
                    break;
            }
            $activities->call_status = ($status == "cancel") ? "canceled" : "rejected";
            $activities->save();

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            Log::error($status.' ['.ucfirst($request->person).'] Meeting Issue : '.$e->getMessage());
            return response()->json(['success' => false, 'error' => 'Failed to '.$status.' meeting. Please try again.']);
        }

        return response()->json(['success' => true, 'message' => "You successfully refuse to attend the meeting"]);
    }
}
