<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Http\Traits\GetDataMeeting_GroupProject_SummaryTrait;
use Illuminate\Http\Request;
use App\Models\GroupMeeting;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    use GetDataMeeting_GroupProject_SummaryTrait;
    protected $student_id;

    public function __construct()
    {
        $this->student_id = Auth::guard('student-api')->check() ? auth()->guard('student-api')->user()->id : NULL;
    }
    
    public function index()
    {
        $data['personal'] = $this->student_call_summary($this->student_id);

        //! tambahin status tidak include yg cancel
        $group_m_upcoming_raw = GroupMeeting::whereHas('group_project', function($query) {
            $query->whereHas('group_participant', function($query1) {
                $query1->where('participants.status', '!=', 2)->where('student_id', $this->student_id);
            });
        })->where('group_meetings.status', 0);
        $group_m_history_raw = GroupMeeting::whereHas('group_project', function($query) {
            $query->whereHas('group_participant', function($query1) {
                $query1->where('student_id', $this->student_id);
            });
        })->where(function ($query) {
            $query->where('group_meetings.status', 1)->orWhere('group_meetings.status', 2);
        });

        // group meeting
        // new request
        // group_m upcoming tidak nge select yang status membernya decline
        $data['group_m'] = array(
            'upcoming' => $group_m_upcoming_raw->count(),
            'history' => $group_m_history_raw->count(),
            'detail' => array(
                'upcoming' => $group_m_upcoming_raw->select(['group_id', 'start_meeting_date', 'end_meeting_date', 'meeting_link', 'meeting_subject'])->get()->makeHidden(['student_attendances', 'user_attendances']),
                'history' => $group_m_history_raw->get()
            )
        );

        $data['group'] = $this->student_group_project_summary($this->student_id);

        return response()->json([
            'success' => true,
            'data' => $data
        ]);
    }
}
