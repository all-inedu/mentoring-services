<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\AcademicRequirement;
use App\Models\UniShortlisted;
use App\Providers\RouteServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class UniversityController extends Controller
{

    protected $student_id;
    protected $STUDENT_UNIVERSITY_SHORTLISTED_VIEW_PER_PAGE;

    public function __construct()
    {
        $this->student_id = auth()->guard('student-api')->user()->id;
        $this->STUDENT_UNIVERSITY_SHORTLISTED_VIEW_PER_PAGE = RouteServiceProvider::STUDENT_UNIVERSITY_SHORTLISTED_VIEW_PER_PAGE;
    }
    
    public function index ($status)
    {
        $status = strtolower($status);
        $uni_shortlisted = UniShortlisted::when($status == 'waitlisted', function($query) {
                                    $query->where('status', 0);
                                })->when($status == 'accepted', function($query) {
                                    $query->where('status', 1);
                                })->when($status == 'applied', function($query) {
                                    $query->where('status', 2);
                                })->when($status == 'rejected', function($query) {
                                    $query->where('status', 3);
                                })->orderBy('uni_name', 'asc')->orderBy('uni_major', 'asc')->
                                paginate($this->STUDENT_UNIVERSITY_SHORTLISTED_VIEW_PER_PAGE);

        return response()->json(['success' => true, 'data' => $uni_shortlisted]);
    }

    //** for university requirement / academic requirement  */
    public function store_requirement (Request $request)
    {
        $rules = [
            'category' => 'required|in:sat,publication_links,ielts,toefl,ap_score',
            'subject.*' => 'required|string|max:255',
            'value.*' => 'required'
        ];

        $validator = Validator::make($request->all() + array('student_id' => $this->student_id), $rules);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'error' => $validator->errors()], 400);
        }

        DB::beginTransaction();
        try {

            for ($i = 0; $i < count($request->subject) ; $i++) {

                //* kalau ada data academic requirement dengan subject dan student id yg sama maka input baru
                if ($academic_req = AcademicRequirement::where('subject', $request->subject[$i])->where('student_id', $this->student_id)->first()) {
                    $academic_req->subject = $request->subject[$i];
                    $academic_req->value = $request->value[$i];
                    $academic_req->save();
                    continue;
                } 
                
                $academic_req = new AcademicRequirement;
                $academic_req->student_id = $request->student_id;
                $academic_req->category = $request->category;
                $academic_req->subject = $request->subject[$i];
                $academic_req->value = $request->value[$i];
                $academic_req->save();


            }

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Add New Academic Requirement Issue : ['.json_encode($request->all()).'] '.$e->getMessage());
            return response()->json(['success' => false, 'error' => 'Failed to add uni requirement. Please try again.']);
        }

        return response()->json(['success' => true, 'message' => 'Uni requirement has been added']);
    }
}
