<?php

namespace App\Http\Controllers;

use App\Models\Students;
use App\Providers\RouteServiceProvider;
use Illuminate\Http\Request;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class StudentController extends Controller
{

    protected $ADMIN_LIST_STUDENT_VIEW_PER_PAGE;

    public function __construct()
    {
        $this->ADMIN_LIST_STUDENT_VIEW_PER_PAGE = RouteServiceProvider::ADMIN_LIST_STUDENT_VIEW_PER_PAGE;
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
    
    public function index()
    {
        $students = Students::orderBy('created_at', 'desc')->paginate($this->ADMIN_LIST_STUDENT_VIEW_PER_PAGE);
        return response()->json(['success' => true, 'data' => $students]);
    }
}
