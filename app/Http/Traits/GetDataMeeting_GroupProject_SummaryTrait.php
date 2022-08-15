<?php

namespace App\Http\Traits;

use App\Models\UniShortlisted;
use App\Models\StudentActivities;
use App\Models\GroupProject;

trait GetDataMeeting_GroupProject_SummaryTrait
{

    public function mentor_group_project_summary($user_id)
    {
        // group project
        // new request
        $data['request'] = GroupProject::whereHas('assigned_mentor', function ($query) use ($user_id) {
            $query->where('user_id', $user_id)->where('group_mentors.status', 0);
        })->count();

        // in progress
        $data['upcoming'] = GroupProject::whereHas('assigned_mentor', function ($query) use ($user_id) {
            $query->where('user_id', $user_id)->where('group_mentors.status', 1);
        })->where('status', 'in progress')->count();

        // history
        $data['history'] = GroupProject::whereHas('assigned_mentor', function ($query) use ($user_id) {
            $query->where('user_id', $user_id)->where('group_mentors.status', 1);
        })->where('status', 'completed')->count();

        return $data;
    }

    public function mentor_call_summary($user_id)
    {
        // 1-on-1 call
        // new request
        $data['request'] = StudentActivities::whereHas('programmes', function($query) {
            $query->where('prog_name', '1-on-1-call');
        })->whereHas('users', function($query) use ($user_id) {
            $query->where('id', $user_id);
        })->where('std_act_status', 'confirmed')->where('mt_confirm_status', 'pending')->where('call_status', 'waiting')->count();

        // pending
        $data['pending'] = StudentActivities::whereHas('programmes', function($query) {
            $query->where('prog_name', '1-on-1-call');
        })->whereHas('users', function($query) use ($user_id) {
            $query->where('id', $user_id);
        })->where('std_act_status', 'waiting')->where('mt_confirm_status', 'confirmed')->where('call_status', 'waiting')->count();

        // upcoming
        $data['upcoming'] = StudentActivities::whereHas('programmes', function($query) {
            $query->where('prog_name', '1-on-1-call');
        })->whereHas('users', function($query) use ($user_id) {
            $query->where('id', $user_id);
        })->where('std_act_status', 'confirmed')->where('mt_confirm_status', 'confirmed')->where('call_status', 'waiting')->count();

        // history (finished, canceled, rejected)
        $data['history'] = StudentActivities::whereHas('programmes', function($query) {
            $query->where('prog_name', '1-on-1-call');
        })->whereHas('users', function($query) use ($user_id) {
            $query->where('id', $user_id);
        })->where(function ($query) {
            $query->where('call_status', 'finished')->orWhere('call_status', 'canceled')->orWhere('call_status', 'rejected');
        })->count();

        return $data;
    }

    public function student_group_project_summary($student_id)
    {
        // group project
        // new request
        $data['request'] = GroupProject::whereHas('group_participant', function ($query) use ($student_id) {
            $query->where('student_id', $student_id)->where('participants.status', 0);
        })->count();

        // in progress
        $data['upcoming'] = GroupProject::whereHas('group_participant', function ($query) use ($student_id) {
            $query->where('student_id', $student_id)->where('participants.status', 1);
        })->where('status', 'in progress')->count();

        // history
        $data['history'] = GroupProject::whereHas('group_participant', function ($query) use ($student_id) {
            $query->where('student_id', $student_id)->where('participants.status', 1);
        })->where('status', 'completed')->count();

        return $data;
    }

    public function student_call_summary($student_id)
    {
        // 1-on-1 call
        // new request
        $data['request'] = StudentActivities::whereHas('programmes', function($query) {
            $query->where('prog_name', '1-on-1-call');
        })->whereHas('students', function($query) use ($student_id) {
            $query->where('id', $student_id);
        })->where('std_act_status', 'waiting')->where('mt_confirm_status', 'confirmed')->where('call_status', 'waiting')->count();

        // pending
        $data['pending'] = StudentActivities::whereHas('programmes', function($query) {
            $query->where('prog_name', '1-on-1-call');
        })->whereHas('students', function($query) use ($student_id) {
            $query->where('id', $student_id);
        })->where('std_act_status', 'confirmed')->where('mt_confirm_status', 'waiting')->where('call_status', 'waiting')->count();

        // upcoming
        $data['upcoming'] = StudentActivities::whereHas('programmes', function($query) {
            $query->where('prog_name', '1-on-1-call');
        })->whereHas('students', function($query) use ($student_id) {
            $query->where('id', $student_id);
        })->where('std_act_status', 'confirmed')->where('mt_confirm_status', 'confirmed')->where('call_status', 'waiting')->count();

        // history (finished, canceled, rejected)
        $data['history'] = StudentActivities::whereHas('programmes', function($query) {
            $query->where('prog_name', '1-on-1-call');
        })->whereHas('students', function($query) use ($student_id) {
            $query->where('id', $student_id);
        })->where(function ($query) {
            $query->where('call_status', 'finished')->orWhere('call_status', 'canceled')->orWhere('call_status', 'rejected');
        })->count();

        return $data;
    }
}