<?php

namespace App\Http\Traits;

use App\Models\StudentActivities;

trait StudentsMeetingSummaryTrait
{

    public function call_summary($student_id)
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