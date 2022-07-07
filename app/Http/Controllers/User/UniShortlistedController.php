<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\CRM\University;
use App\Models\UniShortlisted;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class UniShortlistedController extends Controller
{
    protected $user;
    protected $user_id;
    
    public function __construct()
    {  
        $this->user = Auth::guard('api')->user();
        $this->user_id = $this->user->id; 
    }

    public function switch($status, Request $request)
    {
        $rules = [
            'uni_sh_id' => 'required|exists:uni_shortlisteds,id',
            'status'   => 'integer|in:0,1,2,3'
        ];

        $custom_message = [
            'prog_id.required' => 'Uni shortlisted Id is required.',
            'prog_id.exists' => 'Uni shortlisted Id is invalid'
        ];

        $validator = Validator::make($request->all() + ['status' => $status], $rules, $custom_message);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'error' => $validator->errors()], 400);
        }

        // all of student that being handled by this mentor login
        $all_student_id = array();
        $students = $this->user->students()->select('students.id')->get();
        foreach ($students as $student) {
            $all_student_id[] = $student->id;
        }
        
        // check if uni shortlisted id adalah uni yg dimiliki oleh student yg dihandle oleh mentor
        if ($shortlist = UniShortlisted::find($request->uni_sh_id)->whereNotIn('student_id', $all_student_id)->first()) {
            return response()->json(['success' => false, 'error' => 'You don\'t have permission to change his/her status of uni shortlisted']);
        }

        DB::beginTransaction();
        try {
            $shortlist = UniShortlisted::find($request->uni_sh_id);
            $old_status = $shortlist->status;

            switch ($status) {
                case 0:
                    $message = "waitlisted";
                    break;
                case 1:
                    $message = "accepted";
                    break;
                case 2:
                    $message = "applied";
                    break;
                case 3:
                    $message = "rejected";
                    break;
            }
            
            if ($old_status == $status) {
                return response()->json(['success' => false, 'error' => 'Uni shortlisted is already on '.$message]);
            }

            $shortlist->status = $status;
            $shortlist->save();
            DB::commit();

        } catch (Exception $e) {

            DB::rollBack();
            Log::error('Switch Status Uni Shortlisted Issue : ['.$request->prog_id.', '.$status.'] '.$e->getMessage());
            return response()->json(['success' => false, 'error' => 'Failed to switch uni shortlisted status. Please try again.']);
        }

        
        
        return response()->json(['success' => true, 'message' => 'The uni shortlisted has been changed to '.$message]);
    }

    public function select($student_id)
    {
        $uni_shortlisted = UniShortlisted::where('student_id', $student_id)->orderBy('imported_id', 'asc');
        if ($uni_shortlisted->count() == 0) {
            return response()->json(['success' => false, 'error' => 'He/she doesn\'t have Uni Shortlisted']);
        }

        $data['waitlisted'] = $uni_shortlisted->where('status', 0)->get();
        $data['accepted'] = $uni_shortlisted->where('status', 1)->get();
        $data['applied'] = $uni_shortlisted->where('status', 2)->get();
        $data['rejected'] = $uni_shortlisted->where('status', 3)->get();

        return response()->json(['success' => true, 'data' => $data]);
    }
    
    public function store(Request $request)
    {
        $rules = [
            'student_id' => 'required|exists:students,id',
            'univ_id.*' => 'required|unique:uni_shortlisteds,imported_id|exists:mysql_crm.tbl_univ,univ_id',
            'major.*' => 'required|string'
        ];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'error' => $validator->errors()], 400);
        }

        DB::beginTransaction();
        try {
            for ($i = 0; $i < count($request->univ_id); $i++) {
                $university = University::where('univ_id', $request->univ_id[$i])->first();
                $university_name = $university->univ_name;

                $shortlisted = new UniShortlisted;
                $shortlisted->user_id = $this->user_id;
                $shortlisted->student_id = $request->student_id;
                $shortlisted->imported_id = $request->univ_id[$i];
                $shortlisted->uni_name = $university_name;
                $shortlisted->uni_major = $request->major[$i];
                $shortlisted->status = 0;
                $shortlisted->save();
            }
            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Create University Shortlisted Issue : '.$e->getMessage());
            return response()->json(['success' => false, 'error' => 'Failed to create university shortlisted. Please try again.']);
        }

        return response()->json(['success' => true, 'message' => 'Uni Shortlisted has been made']);
    }
}
