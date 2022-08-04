<?php

namespace App\Http\Controllers;

use App\Models\GroupProject;
use App\Providers\RouteServiceProvider;
use Illuminate\Http\Request;

class GroupProjectController extends Controller
{
    protected $ADMIN_LIST_GROUP_PROJECT_ALL_PER_PAGE;

    public function __construct()
    {
        $this->ADMIN_LIST_GROUP_PROJECT_ALL_PER_PAGE = RouteServiceProvider::ADMIN_LIST_GROUP_PROJECT_ALL_PER_PAGE;
    }

    public function index()
    {
        $group_project = GroupProject::select(['id', 'project_name', 'project_type', 'project_desc', 'progress_status', 'status'])->
                    withCount(['group_participant as total_member', 'assigned_mentor as total_mentor'])->paginate($this->ADMIN_LIST_GROUP_PROJECT_ALL_PER_PAGE);
        return response()->json(['success' => true, 'data' => $group_project]);
    }
}
