<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;
use App\Models\GroupMeeting;
use App\Models\GroupProject;
use Illuminate\Support\Carbon;
use App\Jobs\ReminderNextGroupMeeting;
use App\Providers\RouteServiceProvider;
use App\Rules\IsJoinGroupChecker;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;

class GroupMeetingController extends Controller
{

    protected $user_id;
    protected $MENTOR_MEETING_VIEW_PER_PAGE;

    public function __construct()
    {
        $this->user_id = Auth::guard('api')->check() ? Auth::guard('api')->user()->id : null;
        $this->MENTOR_MEETING_VIEW_PER_PAGE = RouteServiceProvider::MENTOR_MEETING_VIEW_PER_PAGE;
    }

    public function attended($group_meet_id)
    {
        if (!$group_meeting = GroupMeeting::find($group_meet_id)) {
            return response()->json(['success' => false, 'error' => 'Couldn\'t find the Group Meeting']);
        }

        $pivot = $group_meeting->user_attendances->where('id', $this->user_id)->first()->pivot;

        DB::beginTransaction();
        try {
            $pivot = $group_meeting->user_attendances->where('id', $this->user_id)->first()->pivot;
            if ($pivot->attend_status == 1) {
                return response()->json(['success' => true, 'message' => 'You already confirmed to attend the group meeting is held at '.date('M d, Y H:i', strtotime($group_meeting->start_meeting_date))]);
            }
            $pivot->attend_status = 1;
            $pivot->save();

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Confirm Mentor Attendance Group Meeting Issue : '.$e->getMessage());
            return response()->json(['success' => false, 'error' => 'Failed to confirm mentor attendance. Please try again.']);
        }

        return response()->json(['success' => true, 'message' => 'You\'ve confirmed to attend the group meeting that is held at '.date('M d, Y H:i', strtotime($group_meeting->start_meeting_date))]);
    }

    public function index($status, $recent = NULL)
    {
        $rules = [
            'status' => 'in:upcoming,attendance'
        ];

        $validator = Validator::make(['status' => $status], $rules);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'error' => $validator->errors()], 400);
        }

        if (($status == "upcoming") && ($recent != NULL)) {
            $data['attendances'] = $this->get_index("attendance", $recent);
            $data['upcoming'] = $this->get_index("upcoming", $recent);
        } else {
            $data = $this->get_index($status, $recent);
        }

        return response()->json(['success' => true, 'data' => $data]);
        
    }

    public function get_index($status, $recent)
    {

        $meetings = GroupMeeting::with('group_project')->withCount('student_attendances as group_member')->whereHas('user_attendances', function($query) use ($status) {
            $query->where('user_id', $this->user_id)
            ->when($status == "upcoming", function($query1) {
                $query1->where('attend_status', 1);
            })->when($status == "attendance", function($query1) {
                $query1->where('attend_status', 0);
            });
        })->whereHas('group_project', function ($query) {
            $query->where('status', 'in progress');
        })->where('status', 0)->recent($recent, $this->MENTOR_MEETING_VIEW_PER_PAGE)->makeHidden(['student_attendances', 'user_attendances']);


        foreach ($meetings as $meeting) {
            
            $meeting['attendance_info'] = array(
                'attend_id' => $meeting->user_attendances->first()->pivot->id,
                'attend_status' => $meeting->user_attendances->first()->pivot->attend_status
            );
        }

        return $meetings;
    }
    
    public function store(Request $request)
    {

        $rules = [
            'group_id' => ['required', new IsJoinGroupChecker($this->user_id)],
            'start_date' => 'required|date_format:Y-m-d H:i|after_or_equal:'.date('Y-m-d'),
            'end_date' => 'required|date_format:Y-m-d H:i|after:start_date',
            // 'meeting_date' => ['required', 'date_format:Y-m-d H:i', 'after_or_equal:'.date('Y-m-d', strtotime("+1 days")), Rule::unique('group_meetings')->where(function ($query) use ($request) {
            //     return $query->where('group_id', $request->group_id);
            // })],
            'meeting_link' => 'required|string|URL',
            'meeting_subject' => 'required|string|max:255'
        ];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'error' => $validator->errors()], 400);
        }

        $from = $request->start_date;
        $to = $request->end_date;

        if ($group_meeting = GroupMeeting::where(function($query) use ($from, $to) {
            $query->whereBetween('start_meeting_date', [$from, $to])
            ->orWhereBetween('end_meeting_date', [$from, $to]);
        })->whereHas('user_attendances', function($query) {
            // adakah student yang memiliki jadwal group meeting di range tgl tersebut
            $query->where('user_id', $this->user_id)->where('attend_status', 1);
        })->where('group_meetings.status', 0)->count() > 0) {
            return response()->json([
                'success' => false, 
                'error' => 'You already have group meeting around '.date('d M Y H:i', strtotime($from)).'. Please make sure you don\'t have any group meeting schedule before creating a new one.',
            ]);
        }

        DB::beginTransaction();
        try {
            $meeting = new GroupMeeting;
            $meeting->group_id = $request->group_id;
            $meeting->start_meeting_date = $from;
            $meeting->end_meeting_date = $to;
            $meeting->meeting_link = $request->meeting_link;
            $meeting->meeting_subject = $request->meeting_subject;
            $meeting->status = $request->status;
            $meeting->save();

            //* get group info
            $group = GroupProject::find($request->group_id);

            //* add participant to attendance
            $participant = $group->group_participant;
            foreach ($participant as $detail) {
                $meeting->student_attendances()->attach($meeting->id, [
                    'student_id' => $detail->id,
                    'created_at' => Carbon::now()
                ]);
            }

            //* add mentor to attendance
            $mentor = $group->assigned_mentor;
            foreach ($mentor as $detail) {
                $meeting->user_attendances()->attach($meeting->id, [
                    'user_id' => $detail->id,
                    'attend_status' => $detail->id == $this->user_id ? 1 : 0,
                    'mail_sent' => $detail->id == $this->user_id ? 1 : 0,
                    'created_at' => Carbon::now()
                ]);
            }

            //* send email to mentor and the other member
            // ReminderNextGroupMeeting::dispatch()->delay(now()->addSeconds(2));

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Create Group Meeting Issue : ['.json_encode($request->all()).'] '.$e->getMessage());
            return response()->json(['success' => false, 'error' => 'Failed to create meeting. Please try again.']);
        }

        return response()->json([
            'success' => true, 'message' => 
            'Your next meeting is on '.date('d F Y', strtotime($from)).' at '.date('H:i', strtotime($from))
        ]);
    }
}
