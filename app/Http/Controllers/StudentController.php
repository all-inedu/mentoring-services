<?php

namespace App\Http\Controllers;

use App\Models\Students;
use App\Providers\RouteServiceProvider;
use Illuminate\Http\Request;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;

class StudentController extends Controller
{

    protected $ADMIN_LIST_STUDENT_VIEW_PER_PAGE;

    public function __construct()
    {
        $this->ADMIN_LIST_STUDENT_VIEW_PER_PAGE = RouteServiceProvider::ADMIN_LIST_STUDENT_VIEW_PER_PAGE;
    }

    //* new
    public function update_students_info(Request $request)
    {
        $student_id = $request->route('student_id');
        if (!$student = Students::find($student_id)) {
            return response()->json(['succes' => false, 'error' => 'Failed to find student.']);
        }

        $rules = [
            'student_info' => 'required|in:application-year,mentee-relationship,parent-relationship,last-update,additional-notes,email',
            'value' => ['required'],
        ];

        if ($request->student_info == "email") {
            array_push($rules['value'], 'email');
        }

        $validator = Validator::make(['student_info' => $request->student_info, 'value' => $request->value], $rules);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'error' => $validator->errors()], 400);
        }

        DB::beginTransaction();
        try {

            switch ($request->student_info) {
                case "application-year":
                    $student->application_year = $request->value;
                    break;
    
                case "mentee-relationship":
                    $student->mentee_relationship = $request->value;
                    break;
    
                case "parent-relationship":
                    $student->parent_relationship = $request->value;
                    break;
    
                case "last-update":
                    $student->last_update = $request->value;
                    break;

                case "additional-notes":
                    $student->additional_notes = $request->value;
                    break;

                case "email":
                    $student->email = $request->value;
                    break;
            }
    
            $student->save();
            DB::commit();
        
        } catch (Exception $e) {

            DB::rollBack();
            Log::error('Update Student Info '.ucwords(str_replace('-', ' ', $request->student_info)).' Issue : '.$e->getMessage());
            return response()->json(['success' => false, 'error' => 'Failed to update '.str_replace('-', ' ', $request->student_info).'. Please try again.']);
        }

        return response()->json(['success' => true, 'message' => ucwords(str_replace('-', ' ', $request->student_info)).' has been updated']);

    }

    public function profile($student_id, $profile_column, Request $request)
    {
        if (!$student = Students::find($student_id)) {
            return response()->json(['success' => false, 'error' => 'Failed to find student.']);
        }

        $rules = [
            'profile_column' => 'required|in:progress-status,tag',
            'progress' => 'nullable|required_if:profile_column,progress-status|in:ontrack,behind,ahead',
            // 'tag' => 'nullable|string|required_if:profile_column,tag|required_if:remove_tag,null',
            'remove_tag' => 'nullable|string'
        ];

        $validator = Validator::make($request->all() + ['profile_column' => $profile_column], $rules);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'error' => $validator->errors()], 400);
        }

        DB::beginTransaction();
        try {
            switch ($request->profile_column) {
                case "progress-status":
                    $student->progress_status = $request->progress;
                    break;

                case "tag":
                    $student_tag = $student->tag == NULL ? [] : $student->tag;
                    if (in_array($request->get('tag'), $student_tag, TRUE)) {
                        return response()->json(['success' => false, 'error' => "Tag already exist"]);
                    }

                    // do this to remove tag
                    if ($request->get('remove_tag')) {
                        $founded_key = array_keys($student_tag, $request->get('remove_tag'));
                        //find array key of whatever tag inputted
                        if (count($founded_key) == 0) {
                            return response()->json(['success' => false, 'error' => 'Tag doesn\'t exist']);
                        }
                        
                        unset($student_tag[0]);
                        $student->tag = implode(", ", $student_tag);
                    }

                    // do this to add tag
                    if ($request->get('tag')) {
                        $array_tag = array_merge($student_tag, array($request->get('tag')));
                        $student->tag = implode(", ", $array_tag);
                    }
                    break;
            }
            $student->save();
            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Update Student '.$profile_column.' Issue : '.$e->getMessage());
            return response()->json(['success' => false, 'error' => 'Failed to update '.$profile_column.'. Please try again.']);
        }

        return response()->json(['success' => true, 'message' => 'Students profile has been updated']);
    }

    public function select ($user_id)
    {
        try {
            $students = Students::whereHas('users', function($query) use ($user_id) {
                $query->where('user_id', $user_id);
            })->orderBy('created_at', 'desc')->paginate($this->ADMIN_LIST_STUDENT_VIEW_PER_PAGE);
        } catch (Exception $e) {
            Log::error('Select Student Use User Id  Issue : ['.$user_id.'] '.$e->getMessage());
            return response()->json(['success' => false, 'error' => 'Failed to select student use User Id. Please try again.']);
        }
        return response()->json(['success' => true, 'data' => $students]);
    }

    //* new update status mentoring
    public function update_status_mentoring ($student_id, Request $request)
    {
        if (!$student = Auth::guard('api')->user()->students()->where('students.id', $student_id)->first()) {
            return response()->json(['success' => false, 'error' => 'Failed to find Student']);
        }

        try {

            Auth::guard('api')->user()->students()->updateExistingPivot($student, ['status' => $request->value], true);
        } catch (Exception $e) {
            Log::error('Update Status Mentoring  Issue : ['.$student->first_name.' '.$student->last_name.'] '.$e->getMessage());
            return response()->json(['success' => false, 'error' => 'Failed to update status mentoring. Please try again.']);
        }

        $status_message = $request->value == 1 ? "activated" : "non-active";

        return response()->json(['success' => true, 'message' => ucwords($student->first_name.' '.$student->last_name).' has been '.$status_message]);

    }


    public function select_by_auth (Request $request)
    {

        $options = [];
        $user_id = auth()->guard('api')->user()->id;
        $paginate = !$request->get('paginate') ? "yes" : $request->get('paginate');
        $is_searching = $request->get('keyword') ? true : false;
        $keyword = $request->get('keyword') != NULL ? $request->get('keyword') : null;
        $use_tag = $request->get('tag') ? true : false;
        $tag = $request->get('tag') != NULL ? $request->get('tag') : null;
        $use_progress_status = $request->get('status') ? true : false;
        $progress_status = $request->get('status') != NULL ? $request->get('status') : NULL;
        $status_mentoring = $request->get('mentoring');

        if ($is_searching) 
            $options['keyword'] = $keyword;
        
        if ($use_tag)
            $options['tag'] = $tag;

        if ($use_progress_status)
            $options['status'] = $progress_status;

        if ($status_mentoring) 
            $options['mentoring'] = $status_mentoring;
        
        $param_status_mentoring = $status_mentoring == "active" ? 1 : 0;

        try {
            // get data mentees
            $students = Auth::guard('api')->user()->students()->
                when($is_searching, function ($query) use ($keyword) {
                    $query->where(function($query1) use ($keyword){
                        $query1->where(DB::raw("CONCAT(`first_name`, ' ', `last_name`)"), 'like', '%'.$keyword.'%')->
                        orWhere('email', 'like', '%'.$keyword.'%')->
                        orWhere('school_name', 'like', '%'.$keyword.'%');
                    });
                })->when($status_mentoring, function ($query) use ($param_status_mentoring) {
                    $query->where('student_mentors.status', '=', $param_status_mentoring);
                })->when($use_tag, function ($query) use ($tag) {
                    $query->where('tag', 'like', '%'.$tag.'%');
                })->when($use_progress_status, function ($query) use ($progress_status) {
                    $query->where('progress_status', 'like', $progress_status);
                })->select(['students.*', 
                                'student_mentors.id as st_mt_id',
                                'student_mentors.start_mentoring',
                                'student_mentors.end_mentoring',
                                'student_mentors.status as status_mentoring'])->orderBy('student_mentors.created_at', 'desc');
            // return $students;
            // $students = Students::whereHas('users', function($query) use ($user_id) {
            //     $query->where('user_id', $user_id);
            // })->when($is_searching, function ($query) use ($keyword) {
            //     $query->where(function($query1) use ($keyword){
            //         $query1->where(DB::raw("CONCAT(`first_name`, ' ', `last_name`)"), 'like', '%'.$keyword.'%')->
            //         orWhere('email', 'like', '%'.$keyword.'%')->
            //         orWhere('school_name', 'like', '%'.$keyword.'%');
            //     });
            // })->when($use_tag, function ($query) use ($tag) {
            //     $query->where('tag', 'like', '%'.$tag.'%');
            // })->when($use_progress_status, function ($query) use ($progress_status) {
            //     $query->where('progress_status', 'like', $progress_status);
            // })->orderBy('created_at', 'desc');
            $response = $students->customPaginate($paginate, $this->ADMIN_LIST_STUDENT_VIEW_PER_PAGE, $options);

            $all = Students::whereHas('users', function($query) use ($user_id) {
                $query->where('user_id', $user_id);
            });

            $select_status = array();
            $column_status = $all->groupBy('progress_status')->select('progress_status')->get();
            foreach ($column_status as $status) {
                $select_status[] = $status->progress_status;
            }
            $select_tag = array();
            $column_tag = $all->groupBy('tag')->select('tag')->get();
            foreach ($column_tag as $tag) {
                $raw_tag = $tag->tag;
                if ($raw_tag) {
                    $count_raw_tag = count($raw_tag);
                    for ($i = 0; $i < $count_raw_tag; $i++) {
                        if (!in_array($raw_tag[$i], $select_tag))
                            array_push($select_tag, $raw_tag[$i]);
                    }
                }
            }
            
        } catch (Exception $e) {
            Log::error('Select Student Use User Id Issue : ['.$user_id.'] '.$e->getMessage());
            return response()->json(['success' => false, 'error' => 'Failed to select student use User Id. Please try again.']);
        }
        sort($select_tag);

        return response()->json(['success' => true, 'option_filter' => array('status' => $select_status, 'tag' => $select_tag), 'data' => $response]);
    }

    public function find(Request $request)
    {
        $keyword = $request->get('keyword');

        try {
            $students = Students::where(function($query) use ($keyword) {
                $query->where(DB::raw("CONCAT(`first_name`, ' ', `last_name`)"), 'like', '%'.$keyword.'%')->orWhere('email', 'like', '%'.$keyword.'%');
            })->paginate($this->ADMIN_LIST_STUDENT_VIEW_PER_PAGE);

            $response = $keyword != NULL ? $students->appends(array('keyword' => $keyword)) : $students;
        } catch (Exception $e) {
            Log::error('Find Student by Keyword Issue : ['.$keyword.'] '.$e->getMessage());
            return response()->json(['success' => false, 'error' => 'Failed to find student by Keyword. Please try again.']);
        }
        return response()->json(['success' => true, 'data' => $response]);
    }
    
    public function index($student_id = NULL, Request $request)
    {
        $is_detail = (($student_id != NULL) || ($request->get('mail') != NULL)) ? 1 : 0;
        $email = $request->get('mail') != NULL ? $request->get('mail') : null;
        //! old - commented
        // $students = Students::with('social_media')->orderBy('created_at', 'desc')->when($student_id != NULL, function($query) use ($student_id) {
        //     $query->where('id', $student_id);
        // })->when($email != NULL, function($query) use ($email) {
        //         $query->where('email', $email);
        // })->paginateChecker($is_detail, $this->ADMIN_LIST_STUDENT_VIEW_PER_PAGE);
        // return response()->json(['success' => true, 'data' => $students]);

        //* New
        $students = Students::with(['social_media', 'users' => function ($query) {
                    $query->orderBy('priority', 'asc');
            }])->orderBy('created_at', 'desc')->when($student_id != NULL, function($query) use ($student_id) {
                $query->where('id', $student_id);
            })->when($email != NULL, function($query) use ($email) {
                    $query->where('email', $email);
            })->paginateChecker($is_detail, $this->ADMIN_LIST_STUDENT_VIEW_PER_PAGE);
        return response()->json(['success' => true, 'data' => $students]);

        // $is_detail = $request->get('mail') != NULL ? 1 : 0;
        // $email = $request->get('mail') != NULL ? $request->get('mail') : null;

        // $students = Students::with('social_media')->orderBy('created_at', 'desc')->when($is_detail, function($query) use ($email) {
        //     $query->where('email', $email);
        // })->paginateChecker($is_detail, $this->ADMIN_LIST_STUDENT_VIEW_PER_PAGE);
        // return response()->json(['success' => true, 'data' => $students]);
    }

    public function _all()
    {
        $students = Students::orderBy('first_name', 'asc')->where('status', 1)->select(['id', 'first_name', 'last_name', 'email'])->get();
        return response()->json(['success' => true, 'data' => $students]);
    }
}
