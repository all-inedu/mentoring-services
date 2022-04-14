<?php

namespace App\Http\Controllers;

use App\Models\StudentActivities;
use App\Models\Students;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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
                $data['student'] = count(auth()->guard('api')->user()->students);
                $data['activities'] = count(auth()->guard('api')->user()->student_activities);
                break;
        }
        

        return response()->json(['success' => true, 'data' => $data]);
        
    }
}
