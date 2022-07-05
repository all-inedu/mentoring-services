<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\GroupProject;
use App\Providers\RouteServiceProvider;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Builder;

class GroupProjectController extends Controller
{
    protected $user;
    protected $user_id;
    protected $MENTOR_GROUP_PROJECT_VIEW_PER_PAGE;

    public function __construct()
    {
        $this->user = $user = Auth::guard('api')->check() ? Auth::guard('api')->user() : null;
        $this->user_id = $user->id;   
        $this->MENTOR_GROUP_PROJECT_VIEW_PER_PAGE = RouteServiceProvider::MENTOR_GROUP_PROJECT_VIEW_PER_PAGE;
    }

    public function index($status)
    {
        $rules = [
            'status' => 'required|string|in:in-progress,completed'
        ];

        $validator = Validator::make(array('status' => $status), $rules);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'error' => $validator->errors()], 400);
        }

        switch ($status) {

            case "in-progress":
                $group_projects = GroupProject::whereHas('assigned_mentor', function ($query) {
                        $query->where('user_id', $this->user_id)->where('group_mentors.status', 1);
                    })->where('status', 'in progress')->withCount([
                        'group_participant' => function (Builder $query) {
                            $query->where('participants.status', '!=', 2);
                        }
                    ])->orderBy('created_at', 'desc')->paginate($this->MENTOR_GROUP_PROJECT_VIEW_PER_PAGE);
                break;

            case "completed":
                $group_projects = GroupProject::whereHas('assigned_mentor', function ($query) {
                    $query->where('user_id', $this->user_id)->where('group_mentors.status', 1);
                })->where('status', 'completed')->withCount([
                    'group_participant' => function (Builder $query) {
                        $query->where('participants.status', '!=', 2);
                    }
                ])->orderBy('created_at', 'desc')->paginate($this->MENTOR_GROUP_PROJECT_VIEW_PER_PAGE);

                break;
        }

        return response()->json(['success' => true, 'data' => $group_projects]);
    }

    public function store(Request $request)
    {
        $rules = [
            'student_id.*'      => 'required|exists:students,id',
            'project_name'    => 'required|string|max:255',
            'project_type'    => 'required|string|max:255|in:group mentoring,profile building mentoring',
            'project_desc'    => 'required',
            'progress_status' => 'nullable|in:on track,behind,ahead',
            'status'          => 'required|in:in progress,completed',
            'owner_type'      => 'required|in:mentor'
        ];

        $input = $request->all();
        $input['project_type'] = strtolower($request->project_type);

        $validator = Validator::make($input, $rules);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'error' => $validator->errors()], 400);
        }

        DB::beginTransaction();
        try {
            $group_projects = new GroupProject;
            $group_projects->user_id = $this->user_id;
            $group_projects->project_name = $request->project_name;
            $group_projects->project_type = $request->project_type;
            $group_projects->project_desc = $request->project_desc;
            $group_projects->status = $request->status;
            $group_projects->owner_type = $request->owner_type;
            $group_projects->save();

            // select all of student that handled by user id <mentor>
            for ($i = 0 ; $i < count($request->student_id) ; $i++) {

                $group_projects->group_participant()->attach($request->student_id[$i], [
                    'status' => 0,
                    'mail_sent_status' => 0,
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now()
                ]);
            }
                
            $group_projects->assigned_mentor()->attach($this->user_id, [
                'group_id' => $group_projects->id,
                'status' => 1,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ]);
            

            DB::commit();

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Create Group Project Issue : ['.json_encode($request->all()).'] '.$e->getMessage());
            return response()->json(['success' => false, 'error' => 'Failed to create group project. Please try again.']);
        }

        return response()->json(['success' => true, 'message' => 'Group Project has been made.', 'data' => $group_projects]);
    }
}
