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

    public function switch($status = null, Request $request)
    {
        $rules = [
            'uni_sh_id' => 'required|exists:uni_shortlisteds,id',
            'status'   => 'nullable|string|in:waitlist,accept,apply,reject'
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
                case "waitlist":
                    $message = "waitlisted";
                    $status_code = 0;
                    break;
                case "accept":
                    $message = "accepted";
                    $status_code = 1;
                    break;
                case "apply":
                    $message = "applied";
                    $status_code = 2;
                    break;
                case "reject":
                    $message = "rejected";
                    $status_code = 3;
                    break;
                default:
                    $message = "shortlisted";
                    $status_code = 99;
                    break;
            }
            
            if ($old_status == $status_code) {
                return response()->json(['success' => false, 'error' => 'University is already on '.$message]);
            }

            $shortlist->status = $status_code;
            $shortlist->save();
            DB::commit();

        } catch (Exception $e) {

            DB::rollBack();
            Log::error('Switch Status Uni Shortlisted Issue : ['.$request->prog_id.', '.$status.'] '.$e->getMessage());
            return response()->json(['success' => false, 'error' => 'Failed to switch uni shortlisted status. Please try again.']);
        }
        
        return response()->json(['success' => true, 'message' => 'The university has been changed to '.$message]);
    }

    public function select($student_id)
    {
        if (!$uni_shortlisted = UniShortlisted::where('student_id', $student_id)->orderBy('imported_id', 'asc')->get()){
            return response()->json(['success' => false, 'error' => 'He/she doesn\'t have Uni Shortlisted']);
        }

        // return $uni_shortlisted->where('status', 0)->unique('id')->values();

        $data['shortlisted'] = $uni_shortlisted->where('status', 99)->unique('id')->values();
        $data['waitlisted'] = $uni_shortlisted->where('status', 0)->unique('id')->values();
        $data['accepted'] = $uni_shortlisted->where('status', 1)->unique('id')->values();
        $data['applied'] = $uni_shortlisted->where('status', 2)->unique('id')->values();
        $data['rejected'] = $uni_shortlisted->where('status', 3)->unique('id')->values();

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

    public function delete($uni_shortlisted_id)
    {
        if (!$unishortlisted = UniShortlisted::find($uni_shortlisted_id)) {
            return response()->json(['success' => false, 'error' => 'Couldn\'t find the University Shortlisted']);
        }

        DB::beginTransaction();
        try {
            $unishortlisted->delete();

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Delete University Shortlisted Issue : '.$e->getMessage());
            return response()->json(['success' => false, 'error' => 'Failed to delete university shortlisted. Please try again.']);
        }

        return response()->json(['success' => true, 'message' => 'Uni Shortlisted has successfully deleted']);
    }
}
