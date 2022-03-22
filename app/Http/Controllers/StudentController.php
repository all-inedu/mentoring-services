<?php

namespace App\Http\Controllers;

use App\Models\Students;
use Illuminate\Http\Request;

class StudentController extends Controller
{
    
    public function index()
    {
        $students = Students::orderBy('created_at', 'desc')->get();
        return response()->json(['success' => true, 'data' => $students]);
    }
}
