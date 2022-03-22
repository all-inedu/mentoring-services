<?php

namespace App\Http\Controllers;

use App\Models\Students;
use App\Models\User;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    
    public function overview()
    {
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

        return response()->json(['success' => true, 'data' => $data]);
        
    }
}
