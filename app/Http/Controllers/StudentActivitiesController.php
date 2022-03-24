<?php

namespace App\Http\Controllers;

use App\Models\Programmes;
use App\Models\StudentActivities;
use Illuminate\Http\Request;
use App\Rules\RolesChecking;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Exception;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\TransactionController;
use App\Models\Students;
use App\Providers\RouteServiceProvider;
use Illuminate\Support\Facades\Auth;

class StudentActivitiesController extends Controller
{
    protected $ADMIN_LIST_PROGRAMME_VIEW_PER_PAGE;

    public function __construct()
    {
        $this->ADMIN_LIST_PROGRAMME_VIEW_PER_PAGE = RouteServiceProvider::ADMIN_LIST_PROGRAMME_VIEW_PER_PAGE;
    }
    
    public function index($programme, $recent = NULL, Request $request)
    {
        // $student_email = Auth::user()->email;
        $student_email = $request->get('mail') != NULL ? $request->get('mail') : null;

        $is_student = Students::where('email', $student_email)->count() > 0 ? true : false;

        $activities = StudentActivities::
            whereHas('programmes', function($query) use ($programme) {
                $query->where('prog_name', $programme);
            })->when($is_student, function($query) use ($student_email) {
                $query->whereHas('students', function ($q) use ($student_email) {
                    $q->where('email', $student_email);
                });
            })->orderBy('created_at', 'desc')->recent($recent, $this->ADMIN_LIST_PROGRAMME_VIEW_PER_PAGE);

        return response()->json(['success' => true, 'data' => $activities]);
    }

    public function store (Request $request)
    {
        /** list of programmes
         * 1. 1-on-1-call
         * 2. contact-mentor
         * 3. webinar
         * 4. event
         * 5. subscription
         */

        $rules = [
            'prog_id' => 'required|exists:programmes,id',  
            'student_id' => 'required|exists:students,id',
            'user_id' => ['nullable', new RolesChecking($request->call_with)],
            'std_act_status' => 'required|in:waiting,confirmed',
            'handled_by' => ['required', new RolesChecking('admin')],
            'location_link' => 'nullable',
            'prog_dtl_id'=> 'nullable|exists:programme_details,id',
            'call_with' => 'required|in:mentor,alumni,editor',
            'module' => 'required|in:life-skills,career-exploration,university-admission,life-university'
        ];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'error' => $validator->errors()], 400);
        }

        DB::beginTransaction();
        try {
            //select programmes 
            $programmes = Programmes::find($request->prog_id);
            $prog_price = $programmes->prog_price; //price that will be inserted into transaction

            // check if the student is the internal student or external
            $student = Students::find($request->student_id);
            $total_amount = ($student->imported_id != NULL) ? 0 : $prog_price; //set to 0 if student is internal student

            $activities = new StudentActivities;
            $activities->prog_id = $request->prog_id;
            $activities->student_id = $request->student_id;
            $activities->user_id = $request->user_id;
            $activities->std_act_status = $request->std_act_status;
            $activities->handled_by = $request->handled_by;
            $activities->location_link = $request->location_link;
            $activities->prog_dtl_id = $request->prog_dtl_id;
            $activities->call_with = $request->call_with;
            $activities->module = $request->module;
            $activities->save();
            $response['activities'] = $activities;
            $st_act_id = $activities->id;

            $data = [
                'student_id' => $request->student_id,
                'st_act_id'   => $st_act_id,
                'amount'       => $prog_price,
                'total_amount' => $total_amount,
                'status'       => 'pending'
            ];

            $transaction = new TransactionController;
            $response['transaction'] = $transaction->store($data);

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Create Student Activities Issue : ['.json_encode($request->all()).'] '.$e->getMessage());
            return response()->json(['success' => false, 'error' => 'Failed to create student activities. Please try again.']);
        }

        return response()->json(['success' => true, 'message' => 'Activities has been created', 'data' => $response]);
    }
}
