<?php

namespace App\Http\Controllers\V2;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class StudentActivitiesController extends Controller
{
    
    public function index_by_student ($programme, $status)
    {
        $rules = [
            'programme' => 'required|exists:programmes,id',
            'status' => 'required|in:new,pending,upcoming,history'
        ];

        $validator = Validator::make(['programme' => $programme, 'status' => $status], $rules);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'error' => $validator->errors()], 400);
        }

        
    }
}
