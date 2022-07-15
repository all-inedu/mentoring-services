<?php

namespace App\Http\Controllers\V2;

use App\Http\Controllers\Controller;
use App\Models\GroupMeeting;
use Illuminate\Http\Request;
use App\Models\Students;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Builder;

class DashboardController extends Controller
{
    public function overview($role)
    {
        switch ($role) {

            case "admin":
                $data['student'] = Students::count();
                $data['mentor'] = User::whereHas('roles', function($query) {
                    $query->where('role_name', 'mentor');
                })->count();
                $data['alumni'] = User::whereHas('roles', function($query) {
                    $query->where('role_name', 'alumni');
                })->count();
                $data['editor'] = User::whereHas('roles', function($query) {
                    $query->where('role_name', 'editor');
                })->count();
                break;

            case "mentor":
                $programme_id = 1; //! untuk 1 on 1 call di hardcode
                $data['total_student'] = count(auth()->guard('api')->user()->students);
                // $data['activities'] = count(auth()->guard('api')->user()->student_activities);

                $data['meeting']['total_new_request'] = Auth::guard('api')->user()->student_activities()->where(function(Builder $query) use ($programme_id) {
                    $query->where('prog_id', $programme_id)->where('std_act_status', 'waiting')->where('mt_confirm_status', 'confirmed')->where('call_status', 'waiting');
                })->count();

                $data['meeting']['total_upcoming'] = Auth::guard('api')->user()->student_activities()->where(function(Builder $query) use ($programme_id) {
                    $query->where('prog_id', $programme_id)->where('std_act_status', 'confirmed')->where('mt_confirm_status', 'confirmed')->where('call_status', 'waiting');
                })->count();

                $data['group_meeting']['total_upcoming'] = GroupMeeting::whereHas('group_project', function($query) {
                    $query->where('user_id', Auth::guard('api')->user()->id);
                })->where('status', 0)->count();
                break;
        }
        

        return response()->json(['success' => true, 'data' => $data]);
    }
}
