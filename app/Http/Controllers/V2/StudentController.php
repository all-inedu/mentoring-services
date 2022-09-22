<?php

namespace App\Http\Controllers\V2;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Students;
use App\Providers\RouteServiceProvider;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

class StudentController extends Controller
{
    protected $ADMIN_LIST_STUDENT_VIEW_PER_PAGE;

    public function __construct()
    {
        $this->ADMIN_LIST_STUDENT_VIEW_PER_PAGE = RouteServiceProvider::ADMIN_LIST_STUDENT_VIEW_PER_PAGE;
    }

    public function index($student_id = NULL, Request $request)
    {
        $keyword = $request->get('keyword');
        $is_detail = (($student_id != NULL) || ($request->get('mail') != NULL)) ? 1 : 0;
        $email = $request->get('mail') != NULL ? $request->get('mail') : null;
        $students = Students::with('social_media')->orderBy('created_at', 'desc')->when($student_id != NULL, function($query) use ($student_id) {
            $query->where('id', $student_id);
        })->when($email != NULL, function($query) use ($email) {
                $query->where('email', $email);
        })->paginateChecker($is_detail, $this->ADMIN_LIST_STUDENT_VIEW_PER_PAGE);
        return response()->json(['success' => true, 'data' => $students]);
    }

    public function select_all(Request $request)
    {
        $options = [];
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
            $students = Students::with('users')->
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
                });

            $response = $students->customPaginate($paginate, $this->ADMIN_LIST_STUDENT_VIEW_PER_PAGE, $options);



            $select_status = array();
            $column_status = Students::groupBy('progress_status')->select('progress_status')->get();
            foreach ($column_status as $status) {
                $select_status[] = $status->progress_status;
            }
            $select_tag = array();
            $column_tag = Students::groupBy('tag')->select('tag')->get();
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
            Log::error('Select All Student '.$e->getMessage());
            return response()->json(['success' => false, 'error' => 'Failed to select all students. Please try again.']);
        }
        sort($select_tag);

        return response()->json(['success' => true, 'option_filter' => array('status' => $select_status, 'tag' => $select_tag), 'data' => $response]);
    }
}
