<?php

namespace App\Http\Controllers;

use App\Models\ApSubject;
use Illuminate\Http\Request;

class APController extends Controller
{
    
    public function index ()
    {
        $ap_list = ApSubject::all();
        return response()->json(['success' => true, 'data' => $ap_list]);
    }
}
