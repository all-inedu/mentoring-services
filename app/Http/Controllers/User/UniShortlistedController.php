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
    protected $user_id;
    
    public function __construct()
    {  
        $this->user_id = Auth::guard('api')->user()->id; 
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
