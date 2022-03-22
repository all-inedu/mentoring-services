<?php

namespace App\Http\Controllers;

use App\Models\Students;
use App\Providers\RouteServiceProvider;
use Illuminate\Http\Request;

class StudentController extends Controller
{

    protected $ADMIN_LIST_STUDENT_VIEW_PER_PAGE;

    public function __construct()
    {
        $this->ADMIN_LIST_STUDENT_VIEW_PER_PAGE = RouteServiceProvider::ADMIN_LIST_STUDENT_VIEW_PER_PAGE;
    }
    
    public function index()
    {
        $students = Students::orderBy('created_at', 'desc')->paginate($this->ADMIN_LIST_STUDENT_VIEW_PER_PAGE);
        return response()->json(['success' => true, 'data' => $students]);
    }
}
