<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TodosController extends Controller
{
    protected $student;
    protected $student_id;

    public function __construct()
    {
        $this->student = Auth::guard('student-api')->check() ? Auth::guard('student-api')->user() : NULL;
        $this->student_id = $this->student->id;
    }
    
    public function index()
    {        
        $todos = $this->student->todos()->orderBy('created_at', 'desc')->get();
        return response()->json($todos);
    }
}
