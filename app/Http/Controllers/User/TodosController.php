<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Student\UniversityController;
use App\Models\PlanToDoList;
use App\Models\Students;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Carbon;

class TodosController extends Controller
{

    protected $user;
    protected $user_id;

    public function __construct()
    {
        $this->user = Auth::guard('api')->check() ? Auth::guard('api')->user() : NULL;
        $this->user_id = $this->user->id;
    }

    // fungsi get todos dan university shortlisted
    public function select_todos_shortlisted($student_id)
    {
        if (!$student = Students::find($student_id))
            return response()->json(['success' => false, 'error' => 'Couldn\'t find the student/mentee']);

        $response_todos = json_decode($this->select($student_id)->getContent());
        $data['todos'] = $response_todos->data;

        $university = new UniversityController;
        $response_shortlisted = json_decode($university->index('shortlisted', $student_id)->getContent());
        $response_waitlisted = json_decode($university->index('waitlisted', $student_id)->getContent());
        $response_accepted = json_decode($university->index('accepted', $student_id)->getContent());
        $response_applied = json_decode($university->index('applied', $student_id)->getContent());
        $response_rejected = json_decode($university->index('rejected', $student_id)->getContent());


        $data['university']['shortlisted'] = $response_shortlisted->data;
        $data['university']['waitlisted'] = $response_waitlisted->data;
        $data['university']['accepted'] = $response_accepted->data;
        $data['university']['applied'] = $response_applied->data;
        $data['university']['rejected'] = $response_rejected->data;

        return response()->json(['success' => true, 'data' => $data]);
    }

    public function delete($todos_id)
    {
        if (!$todos = PlanToDoList::find($todos_id))
            return response()->json(['success' => false, 'error' => 'Couldn\'t find the Id']);

        DB::beginTransaction();
        try {
            $todos->delete();
            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Delete To Do List Issue : '.$e->getMessage());
            return response()->json(['success' => false, 'error' => 'Failed to delete to do list. Please try again.']);
        }

        return response()->json(['success' => true, 'message' => 'Todos successfully deleted']);
    }

    public function select($student_id)
    {
        if (!$student = Students::find($student_id))
            return response()->json(['success' => false, 'error' => 'Couldn\'t find the student/mentee']);

        $data['waiting'] = $student->todos()->where('plan_to_do_lists.status', 0)->orWhere('plan_to_do_lists.status', 2)->get();
        $data['confirmation_need'] = $student->todos()->where('plan_to_do_lists.status', 1)->get();
        $data['completed'] = $student->todos()->where('plan_to_do_lists.status', 3)->get();

        return response()->json(['success' => true, 'data' => $data]);
    }

    public function switch(Request $request)
    {
        $rules = [
            'todos_id' => 'required|exists:plan_to_do_lists,id',
            'new_status' => 'required|integer|in:0,1,2,3',
        ];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'error' => $validator->errors()], 400);
        }

        DB::beginTransaction();
        try {
            $todos = PlanToDoList::find($request->todos_id);
            $todos->status = $request->new_status;
            $todos->save();

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Create To Do List Issue : ['.json_encode($request->all()).'] '.$e->getMessage());
            return response()->json(['success' => false, 'error' => 'Failed to create to do list. Please try again.']);
        }

        return response()->json(['success' => true, 'message' => 'Todos status has been changed']);
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

        // check if the student mentored by user id login
        $student = Students::find($request->student_id);
        if(!$find = $student->users()->where('users.id', $this->user_id)->first()) {
            return response()->json(['success' => false, 'error' => 'You don\'t have permission to add new todos for this student/mentee']);
        }

        $student_mentors_id = $find->pivot->id;

        DB::beginTransaction();
        try {
            $todos = new PlanToDoList;
            $todos->student_mentors_id = $student_mentors_id;
            $todos->task_name = $request->task_name;
            $todos->description = $request->description;
            $todos->due_date = $request->due_date;
            $todos->save();

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Create To Do List Issue : ['.json_encode($request->all()).'] '.$e->getMessage());
            return response()->json(['success' => false, 'error' => 'Failed to create to do list. Please try again.']);
        }

        return response()->json(['success' => true, 'message' => 'New todos for '.$student->first_name.' '.$student->last_name.' has been added']);
    }
}
