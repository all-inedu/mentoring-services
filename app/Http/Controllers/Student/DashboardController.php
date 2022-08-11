<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\GroupMeeting;
use App\Http\Traits\StudentsGroupProjectSummaryTrait as TraitsStudentsGroupProjectSummaryTrait;
use App\Http\Traits\StudentsMeetingSummaryTrait as TraitsStudentsMeetingSummaryTrait;

class DashboardController extends Controller
{
    use TraitsStudentsMeetingSummaryTrait;
    use TraitsStudentsGroupProjectSummaryTrait;
    
    public function index()
    {
        $data['personal'] = $this->call_summary($this->student_id);

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
                'upcoming' => $group_m_upcoming_raw->select(['group_id', 'meeting_date', 'meeting_link', 'meeting_subject'])->get()->makeHidden(['student_attendances', 'user_attendances']),
                'history' => $group_m_history_raw->get()
            )
        );

        $data['group'] = $this->group_project_summary($this->student_id);

        return response()->json([
            'success' => true,
            'data' => $data
        ]);
    }
}
