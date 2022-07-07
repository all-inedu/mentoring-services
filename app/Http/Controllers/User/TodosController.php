<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\PlanToDoList;
use App\Models\Students;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TodosController extends Controller
{

    protected $user;
    protected $user_id;

    public function __construct()
    {
        $this->user = Auth::guard('api')->check() ? Auth::guard('api')->user() : NULL;
        $this->user_id = $this->user->id;
    }
    
    public function store (Request $request)
    {
        $rules = [
            'student_id' => 'required|exists:students,id',
            'task_name' => 'required',
            'description' => 'required',
            'due_date' => 'required|date',
        ];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'error' => $validator->errors()], 400);
        }

        $student = Students::find($request->student_id);

        DB::beginTransaction();
        try {
            $todos = new PlanToDoList;
            

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Create To Do List Issue : ['.json_encode($request->all()).'] '.$e->getMessage());
            return response()->json(['success' => false, 'error' => 'Failed to create to do list. Please try again.']);
        }

        return response()->json(['success' => true, 'message' => 'New todos for '.$student->first_name.' '.$student->last_name.' has been added']);
    }
}
