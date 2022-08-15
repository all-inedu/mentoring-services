<?php

namespace App\Http\Traits;

use App\Models\StudentActivities;

trait MentorsMeetingSummaryTrait
{

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
}