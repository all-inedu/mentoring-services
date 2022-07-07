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
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;

class GroupMeetingController extends Controller
{

    protected $user_id;

    public function __construct()
    {
        $this->user_id = Auth::guard('api')->check() ? Auth::guard('api')->user()->id : null;
    }
    
    public function store(Request $request)
    {
        $rules = [
            'group_id' => ['required', Rule::exists('group_projects', 'id')->where(function($query) {
                $query->where('user_id', $this->user_id);
            })],
            'meeting_date' => ['required', 'date_format:Y-m-d H:i', Rule::unique('group_meetings')->where(function ($query) use ($request) {
                return $query->where('group_id', $request->group_id);
            })],
            'meeting_link' => 'required|string|URL',
            'meeting_subject' => 'required|string|max:255'
        ];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'error' => $validator->errors()], 400);
        }

        // $request_date = $request->meeting_date;
        // $hour_before = date('Y-m-d H:i', strtotime("-1 hour", strtotime($request_date)));
        // $hour_after = date('Y-m-d H:i', strtotime("+1 hour", strtotime($request_date)));

        // if ($group_meeting = GroupMeeting::where(function($query) use ($request_date, $hour_before, $hour_after) {
        //     $query->whereBetween('meeting_date', [$hour_before, $request_date])
        //     ->orWhereBetween('meeting_date', [$request_date, $hour_after]);
        // })->whereHas('user_attendances', function($query) {
        //     // adakah student yang memiliki jadwal group meeting di range tgl tersebut
        //     $query->where('user_id', $this->user_id)->where('attend_status', 1);
        // })->where('group_meetings.status', 0)->count() > 0) {
        //     return response()->json([
        //         'success' => false, 
        //         'error' => 'You already have group meeting around '.date('d M Y H:i', strtotime($request_date)).'. Please make sure you don\'t have any group meeting schedule before creating a new one.',
        //     ]);
        // }

        DB::beginTransaction();
        try {
            $meeting = new GroupMeeting;
            $meeting->group_id = $request->group_id;
            $meeting->meeting_date = $request->meeting_date;
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
            ReminderNextGroupMeeting::dispatch()->delay(now()->addSeconds(2));

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Create Group Meeting Issue : ['.json_encode($request->all()).'] '.$e->getMessage());
            return response()->json(['success' => false, 'error' => 'Failed to create meeting. Please try again.']);
        }

        return response()->json([
            'success' => true, 'message' => 
            'Your next meeting is on '.date('d F Y', strtotime($request->meeting_date)).' at '.date('H:i', strtotime($request->meeting_date))
        ]);
    }
}
