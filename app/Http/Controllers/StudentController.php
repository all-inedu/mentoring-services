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

class StudentController extends Controller
{

    protected $ADMIN_LIST_STUDENT_VIEW_PER_PAGE;

    public function __construct()
    {
        $this->ADMIN_LIST_STUDENT_VIEW_PER_PAGE = RouteServiceProvider::ADMIN_LIST_STUDENT_VIEW_PER_PAGE;
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

    public function select_by_auth (Request $request)
    {
        $paginate = !$request->get('paginate') ? "yes" : $request->get('paginate');
        $is_searching = $request->get('keyword') ? true : false;
        $keyword = $request->get('keyword') != NULL ? $request->get('keyword') : null;
        $user_id = auth()->guard('api')->user()->id;
        try {
            $students = Students::whereHas('users', function($query) use ($user_id) {
                $query->where('user_id', $user_id);
            })->when($is_searching, function ($query) use ($keyword) {
                $query->where(DB::raw("CONCAT(`first_name`, ' ', `last_name`)"), 'like', '%'.$keyword.'%')->
                    orWhere('email', 'like', '%'.$keyword.'%')->
                    orWhere('school_name', 'like', '%'.$keyword.'%');
            })->orderBy('created_at', 'desc')->customPaginate($paginate, $this->ADMIN_LIST_STUDENT_VIEW_PER_PAGE);
            
        } catch (Exception $e) {
            Log::error('Select Student Use User Id  Issue : ['.$user_id.'] '.$e->getMessage());
            return response()->json(['success' => false, 'error' => 'Failed to select student use User Id. Please try again.']);
        }
        return response()->json(['success' => true, 'data' => $students]);
    }

    public function find(Request $request)
    {
        $keyword = $request->get('keyword');

        try {
            $students = Students::where(function($query) use ($keyword) {
                $query->where(DB::raw("CONCAT(`first_name`, ' ', `last_name`)"), 'like', '%'.$keyword.'%')->orWhere('email', 'like', '%'.$keyword.'%');
            })->paginate($this->ADMIN_LIST_STUDENT_VIEW_PER_PAGE);
        } catch (Exception $e) {
            Log::error('Find Student by Keyword Issue : ['.$keyword.'] '.$e->getMessage());
            return response()->json(['success' => false, 'error' => 'Failed to find student by Keyword. Please try again.']);
        }
        return response()->json(['success' => true, 'data' => $students]);
    }
    
    public function index(Request $request)
    {
        $is_detail = $request->get('mail') != NULL ? 1 : 0;
        $email = $request->get('mail') != NULL ? $request->get('mail') : null;

        $students = Students::with('social_media')->orderBy('created_at', 'desc')->when($is_detail, function($query) use ($email) {
            $query->where('email', $email);
        })->paginateChecker($is_detail, $this->ADMIN_LIST_STUDENT_VIEW_PER_PAGE);
        return response()->json(['success' => true, 'data' => $students]);
    }
}
