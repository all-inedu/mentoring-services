<?php

namespace App\Http\Controllers;

use App\Models\GroupProject;
use Illuminate\Http\Request;

class GroupProjectController extends Controller
{
    public function index()
    {
        $group_project = GroupProject::select(['id', 'project_name', 'project_type', 'project_desc', 'progress_status', 'status'])->
                    with(['group_participant:id,first_name,last_name', 'assigned_mentor:id,first_name,last_name'])->get();
        return response()->json(['success' => true, 'data' => $group_project]);
    }
}
