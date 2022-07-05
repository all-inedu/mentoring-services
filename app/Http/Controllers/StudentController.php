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
            'tag' => 'nullable|required_if:profile_column,tag',
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
                    $student->tag = $request->tag;
                    break;
            }
            $student->save();
            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Update Student Progress Status Issue : '.$e->getMessage());
            return response()->json(['success' => false, 'error' => 'Failed to update progress status. Please try again.']);
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
            })->orderBy('created_at', 'desc')->paginate($this->ADMIN_LIST_STUDENT_VIEW_PER_PAGE);
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
