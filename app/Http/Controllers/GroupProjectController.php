<?php

namespace App\Http\Controllers;

use App\Models\GroupProject;
use Illuminate\Http\Request;

class GroupProjectController extends Controller
{
    public function index()
    {
        $group_project = GroupProject::select(['id', 'project_name', 'project_type', 'project_desc', 'progress_status', 'status'])->
                    withCount(['group_participant as total_member', 'assigned_mentor as total_mentor'])->get();
        return response()->json(['success' => true, 'data' => $group_project]);
    }
}
