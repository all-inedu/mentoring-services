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
use App\Http\Controllers\MailLogController;
use App\Http\Traits\GetDataMeeting_GroupProject_SummaryTrait;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Carbon;

class StudentActivitiesController extends Controller
{
    use GetDataMeeting_GroupProject_SummaryTrait;
    protected $student_id;
    protected $STUDENT_MEETING_VIEW_PER_PAGE;
    protected $TO_MENTEES_1ON1CALL_SUBJECT;

    public function __construct()
    {
        $this->student_id = Auth::guard('student-api')->check() ? auth()->guard('student-api')->user()->id : NULL;
        $this->user_id = Auth::guard('api')->check() ? Auth::guard('api')->user()->id : NULL;
        $this->STUDENT_MEETING_VIEW_PER_PAGE = RouteServiceProvider::STUDENT_MEETING_VIEW_PER_PAGE;
        $this->ADMIN_LIST_PROGRAMME_VIEW_PER_PAGE = RouteServiceProvider::ADMIN_LIST_PROGRAMME_VIEW_PER_PAGE;
        $this->TO_MENTEES_1ON1CALL_SUBJECT = RouteServiceProvider::TO_MENTEES_1ON1CALL_SUBJECT;
    }

    public function meeting_log($student_id)
    {
        $programme = '1-on-1-call';
        $meeting = StudentActivities::with(['students', 'users', 'meeting_minutes'])->whereHas('programmes', function($query) use ($programme) {
            $query->where('prog_name', $programme);
        })->where('student_id', $student_id)->where('call_status', 'finished')->orderBy('start_call_date', 'desc')->paginate(10);
        
        return response()->json(['success' => true, 'data' => $meeting]);
    }

    public function finish_meeting($meeting_id)
    {
        if (!$meeting = StudentActivities::where('id', $meeting_id)->where('user_id', $this->user_id)->where('std_act_status', 'confirmed')->where('mt_confirm_status', 'confirmed')->first()) {
            return response()->json(['success' => false, 'error' => 'Couldn\'t find the meeting']);
        }

        $call_status = $meeting->call_status;
        if (($call_status == "finished") || ($call_status == "rejected") || ($call_status == "canceled")) {
            return response()->json(['success' => false, 'error' => 'The status meeting cannot be changed']);
        }

        DB::beginTransaction();
        try {
            $meeting->call_status = "finished";
            $meeting->save();
            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Manually finish personal meeting Issue : '.$e->getMessage());
            return response()->json(['success' => false, 'error' => 'Couldn\'t update meeting status. Please try again.']);
        }

        return response()->json(['success' => true, 'message' => 'The meeting has been updated']);
    }

    public function mentors_group_project_summary()
    {
        try {
            $response = $this->mentor_group_project_summary($this->user_id);
        } catch (Exception $e) {
            Log::error('Get Mentor Group Project Summary Issue : '.$e->getMessage());
            return response()->json(['success' => false, 'error' => 'Couldn\'t get summary. Please try again.']);
        }

        return response()->json(['success' => true, 'data' => $response]);
    }

    public function mentors_meeting_summary()
    {
        try {
            $response = $this->mentor_call_summary($this->user_id);
        } catch (Exception $e) {
            Log::error('Get Mentor Meeting Summary Issue : '.$e->getMessage());
            return response()->json(['success' => false, 'error' => 'Couldn\'t get summary. Please try again.']);
        }
        
        return response()->json(['success' => true, 'data' => $response]);
    }

    public function students_group_project_summary()
    {
        try {
            $response = $this->student_group_project_summary($this->student_id);
        } catch (Exception $e) {
            Log::error('Get Student Group Project Summary Issue : '.$e->getMessage());
            return response()->json(['success' => false, 'error' => 'Couldn\'t get summary. Please try again.']);
        }

        return response()->json(['success' => true, 'data' => $response]);
    }

    public function students_meeting_summary()
    {
        try {
            $response = $this->student_call_summary($this->student_id);
        } catch (Exception $e) {
            Log::error('Get Student Meeting Summary Issue : '.$e->getMessage());
            return response()->json(['success' => false, 'error' => 'Couldn\'t get summary. Please try again.']);
        }

        return response()->json(['success' => true, 'data' => $response]);
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
            'module.*' => 'required_if:activities,1-on-1-call|distinct|in:life skills,career exploration,admissions mentoring,life at university',
            'start_date' => ['required_if:activities,1-on-1-call', 'date', 'after_or_equal:'. date('Y-m-d')/*, new CheckAvailabilityUserSchedule($request->user_id)*/],
            'end_date' => ['required_if:activities,1-on-1-call', 'date', 'after:start_date'],
            'created_by' => 'required|in:mentor,editor,alumni'
        ];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'error' => $validator->errors()], 400);
        }
        
        DB::beginTransaction();
        try {

            $from = $request->start_date;
            $to = $request->end_date;

            if (StudentActivities::where('call_status', 'waiting')->where(function($query) use ($from, $to) {
                $query->whereBetween('start_call_date', [$from, $to])->orWhereBetween('end_call_date', [$from, $to]);
            })->where('user_id', $this->user_id)->count() > 0) {
                return response()->json(['success' => false, 'error' => 'You already have a meeting around '.date('d M Y H:i', strtotime($from)).'. Please make sure you don\'t have any meeting schedule before creating a new one.']);
            }

            $module = NULL;
            for ($i = 0; $i < count($request->module) ; $i++) {
                $module .= $module != NULL ? ', '.$request->module[$i] : $request->module[$i]; 
            }

            $request_data = [
                'prog_id' => $programme_id, 
                'student_id' => $student_id,
                'user_id' => $this->user_id,
                'std_act_status' => 'waiting',
                'mt_confirm_status' => 'confirmed',
                'handled_by' => NULL, //! for now set to null
                'location_link' => $request->location_link,
                'location_pw' => $request->location_pw,
                'prog_dtl_id' => NULL,
                'call_with' => $request->call_with,
                'module' => $module,
                'start_call_date' => $from,
                'end_call_date' => $to,
                'call_status' => 'waiting',
                'created_by' => $request->created_by
            ];

            //select programmes 
            $programmes = Programmes::find($programme_id);
            $prog_price = $programmes->prog_price; //price that will be inserted into transaction

            // check if the student is the internal student or external
            $student = Students::find($student_id);
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

        // send mail notification to mentees
        $mentees_info = [
            'name' => $student->first_name.' '.$student->last_name,
            'email' => $student->email,
        ];
        
        $data_mail = [
            'meeting_id' => $activities->id, 
            'name' => $student->first_name.' '.$student->last_name,
            'mentor_name' => $activities->users->first_name.' '.$activities->users->last_name,
            'module' => $activities->module,
            'call_date' => $activities->start_call_date,
            'location_link' => $activities->location_link,
            'location_pw' => $activities->location_pw 
        ];

        Mail::send('templates.mail.to-mentees.next-meeting-announcement', $data_mail, function($mail) use ($mentees_info)  {
            $mail->from(env('MAIL_FROM_ADDRESS'), env('MAIL_FROM_NAME'));
            $mail->to($mentees_info['email'], $mentees_info['name']);
            $mail->subject($this->TO_MENTEES_1ON1CALL_SUBJECT);
        });

        if (count(Mail::failures()) > 0) { 

            // save to log mail admin
            // save only if failure to sent
            $log = array(
                'sender'    => 'mentor',
                'recipient' => $mentees_info['email'],
                'subject'   => $this->TO_MENTEES_1ON1CALL_SUBJECT,
                'message'   => 'Sending notification that mentor has invite the Student to do 1 on 1 call ['.json_encode($data_mail).']',
                'date_sent' => Carbon::now(),
                'status'    => "not delivered",
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            );
            $save_log = new MailLogController;
            $save_log->saveLogMail($log);
            
            foreach (Mail::failures() as $email_address) {
                Log::error('Send Notification to Mentee, Mentor Created 1 on 1 Call Issue  : ['.$email_address.']');
            }
        } 

        return response()->json(['success' => true, 'message' => '1 on 1 Call has been made', 'data' => $response['activities']]);
    }

    public function index($programme, $status = NULL, $recent = NULL, Request $request)
    {
        $meeting_minutes = $request->get('meeting-minutes') == "yes" ? $request->get('meeting-minutes') : null;

        $rules = [
            'programme' => 'required|in:1-on-1-call,webinar,event',
            'status' => 'nullable|in:new,pending,upcoming,history',
            'filter' => 'nullable|in:rejected,finished,canceled'
        ];

        $validator = Validator::make(['programme' => $programme, 'status' => $status], $rules);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'error' => $validator->errors()], 400);
        }

        $filter_status = NULL;
        if ($filter = $request->get('filter')) {
            $exp = explode(',', $filter);
            for ($i = 0 ; $i < count($exp) ; $i++) {
                $filter_status[] = $exp[$i];
            }
        }

        // kondisi buat nampilin data khusus yg di dashboard mentor
        if (($recent != NULL) && ($status == "upcoming")) {
            $data['upcoming'] = $this->get_index($programme, $status, $recent, $meeting_minutes, null);
            $data['latest_meeting'] = $this->get_index($programme, 'history', $recent, "yes")->where('meeting_minute', 0)->unique('id')->values();
        } else {
            $data = $this->get_index($programme, $status, $recent, $meeting_minutes, $filter_status);
        }

        return response()->json(['success' => true, 'data' => $data]);
    }

    public function get_index($programme, $status, $recent, $meeting_minutes, $filter_status=NULL)
    {
        $activities = StudentActivities::with(['students', 'users'])->withCount('meeting_minutes as meeting_minute')->where('user_id', $this->user_id)
        ->when($status == 'new', function($query) {
            $query->where('std_act_status', 'confirmed')->where('mt_confirm_status', 'waiting')->where('call_status', 'waiting')
            ->orderBy('call_status', 'desc')
            ->orderBy('start_call_date', 'asc');
        })
        ->when($status == 'pending', function($query) {
            $query->where('std_act_status', 'waiting')->where('mt_confirm_status', 'confirmed')->where('call_status', 'waiting')
            ->orderBy('call_status', 'desc')
            ->orderBy('start_call_date', 'asc');
        })
        ->when($status == 'upcoming', function($query) {
            $query->where('std_act_status', 'confirmed')->where('mt_confirm_status', 'confirmed')->where('call_status', 'waiting')
            ->orderBy('call_status', 'desc')
            ->orderBy('start_call_date', 'asc');
        })
        ->when($status == 'history', function($query) use ($meeting_minutes, $filter_status) {
            $query->when($meeting_minutes == NULL, function ($query1) use ($filter_status) {
                $query1->when($filter_status, function ($q2) use ($filter_status) {
                    $q2->whereIn('call_status', $filter_status);
                }, function($q2) {
                    $q2->where(function($q1) { 
                        $q1->where('call_status', 'finished')->orWhere('call_status', 'canceled')->orWhere('call_status', 'rejected');
                    });
                });
            }, function($query1) {
                $query1->where('call_status', 'finished');
            })
            ->orderBy('call_status', 'desc')
            ->orderBy('start_call_date', 'desc');
        })
        ->whereHas('programmes', function($query) use ($programme) {
            $query->where('prog_name', $programme);
        })
        ->recent($recent, $this->ADMIN_LIST_PROGRAMME_VIEW_PER_PAGE);

        return $activities;
    }
    
    public function index_by_student ($person, $programme, $status = NULL, $recent = NULL, Request $request)
    {
        $webinar_category = $request->get('category');
        $rules = [
            'person' => 'required|in:mentor,student',
            'programme' => 'required|in:1-on-1-call,webinar,event',
            'status' => 'nullable|in:new,pending,upcoming,history',
            'filter' => 'nullable|in:rejected,finished,canceled'
        ];
        
        if ($programme == "webinar") {
            $rules['category'] = $webinar_category != "" ? 'exists:programme_details,dtl_category' : '';
        }

        $validator = Validator::make(['person' => $person, 'programme' => $programme, 'status' => $status, 'category' => $webinar_category], $rules);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'error' => $validator->errors()], 400);
        }

        // if (($person == "mentor") && (!$request->get('student'))) {
        //     return response()->json(['success' => false, 'error' => 'Couldn\'t find the students webinar history']);
        // } else if (($person == "student") && ($request->get('student'))) {
        //     return response()->json(['success' => false, 'error' => 'No access']);
        // }
        
        $student_id = $request->get('student') ? $request->get('student') : $this->student_id;

        // $using_status = $request->get('status') ? 1 : 0;
        // $status = $request->get('status') != NULL ? $request->get('status') : false;
        $use_keyword = $request->get('keyword') ? 1 : 0;
        $keyword = $request->get('keyword') != NULL ? $request->get('keyword') : null;
        
        $filter_status = NULL;
        if ($filter = $request->get('filter')) {
            $exp = explode(',', $filter);
            for ($i = 0 ; $i < count($exp) ; $i++) {
                $filter_status[] = $exp[$i];
            }
        }

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
            ->orderBy('start_call_date', 'asc');
        })->when($status == 'pending', function ($q) {
            $q->where('std_act_status', 'confirmed')->where('mt_confirm_status', 'waiting')->where('call_status', 'waiting')
            ->orderBy('call_status', 'desc')
            ->orderBy('start_call_date', 'asc');
        })->when($status == 'upcoming', function ($q) {
            $q->where('std_act_status', 'confirmed')->where('mt_confirm_status', 'confirmed')->where('call_status', 'waiting')
            ->orderBy('call_status', 'desc')
            ->orderBy('start_call_date', 'asc');
        })->when($status == "history", function ($q) use ($filter_status) {
            $q->when($filter_status, function ($q2) use ($filter_status) {
                $q2->whereIn('call_status', $filter_status);
            }, function($q2) {
                $q2->where(function($q1) { 
                    $q1->where('call_status', 'finished')->orWhere('call_status', 'canceled')->orWhere('call_status', 'rejected');
                })
                ->orderBy('start_call_date', 'desc');
            });
        })->get();

        return response()->json(['success' => true, 'data' => $helper->paginate($activities)->appends(array('student' => $student_id, 'filter' => $filter))]);
    }
}
