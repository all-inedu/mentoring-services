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

    public function index(Request $request)
    {
        $status = $request->get('status');
        $keyword = $request->get('keyword');

        $group_project = GroupProject::select(['id', 'project_name', 'project_type', 'project_desc', 'progress_status', 'status'])->
                    withCount(['group_participant as total_member', 'assigned_mentor as total_mentor'])->
                        when($keyword != NULL, function ($query) use ($keyword) {
                            $query->where('project_name', 'LIKE', '%'.$keyword.'%');
                        })->
                        when($status == "ongoing", function ($query) {
                            $query->where('status', 'in progress');
                        })->
                        when($status == "completed", function ($query) {
                            $query->where('status', 'completed');
                        })->
                        paginate($this->ADMIN_LIST_GROUP_PROJECT_ALL_PER_PAGE);

        if ($keyword != NULL) {
            $options['keyword'] = $keyword;
        }

        if ($status != NULL) {
            $options['status'] = $status;
        }

        return response()->json(['success' => true, 'data' => $group_project->appends($options)]);
    }
}
