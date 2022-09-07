<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Http\Controllers\HelperController;
use App\Http\Traits\GetDataUniversityShortlistedTrait;
use App\Models\AcademicRequirement;
use App\Models\Medias;
use App\Models\UniRequirementMedia;
use App\Models\UniShortlisted;
use App\Providers\RouteServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\URL;
use App\Http\Traits\UploadMediaTrait;
use App\Models\MediaCategory;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class UniversityController extends Controller
{
    use UploadMediaTrait;
    use GetDataUniversityShortlistedTrait;
    protected $student_id;
    protected $STUDENT_UNIVERSITY_SHORTLISTED_VIEW_PER_PAGE;

    public function __construct()
    {
        $this->student_id = Auth::guard('student-api')->check() ? auth()->guard('student-api')->user()->id : null;
        $this->STUDENT_UNIVERSITY_SHORTLISTED_VIEW_PER_PAGE = RouteServiceProvider::STUDENT_UNIVERSITY_SHORTLISTED_VIEW_PER_PAGE;
    }

    public function summary()
    {
        $data = array(
            'shortlisted' => $this->get($this->student_id, 'shortlisted')->count(),
            'applied' => $this->get($this->student_id, 'applied')->count(),
            'accepted' => $this->get($this->student_id, 'accepted')->count(),
            'rejected' => $this->get($this->student_id, 'rejected')->count(),
            'waitlisted' => $this->get($this->student_id, 'waitlisted')->count(),
        );

        return response()->json(['success' => true, 'data' => $data]);
    }
    
    public function index ($status, $student_id = NULL)
    {
        $rules = [
            'status' => 'in:waitlisted,accepted,applied,rejected,shortlisted,all'
        ];

        $validator = Validator::make(['status' => $status], $rules);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'error' => $validator->errors()], 400);
        }

        $id = ($student_id != NULL) ? $student_id : $this->student_id;
        $status = strtolower($status);
        $response = $this->get($id, $status);

        return response()->json(['success' => true, 'data' => $response]);
    }

    public function index_requirement($category, $show_item = null)
    {
        $rules = [
            'category' => 'in:academic,document'
        ];

        if (($show_item != null) && ($show_item != "all")) {
            $rules['show_item'] = 'exists:uni_shortlisteds,imported_id';
        }
        
        $validator = Validator::make(['category' => $category], $rules);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'error' => $validator->errors()], 400);
        }

        switch ($category) {
            case "academic":
                return $this->index_academic_requirement();
                break;

            case "document":
                return $this->index_document_requirement(null, $show_item);
                break;
        }
    }

    public function index_academic_requirement ($student_id = null)
    {
        $id = $student_id != null ? $student_id : $this->student_id;
        $academic = AcademicRequirement::where('student_id', $id)->orderBy('category', 'asc')->get()->makeHidden(['created_at', 'updated_at']);
        $data = collect($academic)->groupBy('category');

        $category = [
            'sat',
            'publication_links',
            'ielts',
            'toefl',
            'ap_score'
        ];

        for ($i = 0 ; $i < count($category) ; $i++) {
            if (!array_key_exists($category[$i], $data->toArray())) {
                $data[$category[$i]] = [];
            }
        }

        return response()->json(['success' => true, 'data' => $data]);
    }

    public function index_document_requirement ($student_id = null, $show_item)
    {
        $id = $student_id != null ? $student_id : $this->student_id;
        $essay_has_uni = Medias::select(['id', 'med_title', 'med_desc', 'med_file_path', 'med_file_name', 'med_file_format'])->
            with('uni_shortlisted:id,imported_id,uni_name,uni_major')->
            whereHas('media_categories', function($query) {
                return $query->where('name', 'Essay');
        })->when($show_item != "all", function ($query) use ($show_item) {
            $query->whereHas('uni_shortlisted', function ($query1) use ($show_item) {
                $query1->where('imported_id', $show_item);
            });
        })->where('student_id', $id)->get();

        $lor_has_uni = Medias::select(['id', 'med_title', 'med_desc', 'med_file_path', 'med_file_name', 'med_file_format'])->
            with('uni_shortlisted:id,imported_id,uni_name,uni_major')->
            whereHas('media_categories', function($query) {
                return $query->where('name', 'Letter of Recommendation');
        })->when($show_item != "all", function ($query) use ($show_item) {
            $query->whereHas('uni_shortlisted', function ($query1) use ($show_item) {
                $query1->where('imported_id', $show_item);
            });
        })->where('student_id', $id)->get()->makeHidden('pivot');
        
        if ($show_item != "all") {
            $essay_doesnt_have_uni = Medias::select(['id', 'med_title', 'med_desc', 'med_file_path', 'med_file_name', 'med_file_format'])->
                with('uni_shortlisted:id,imported_id,uni_name,uni_major')->
                whereHas('media_categories', function($query) {
                    return $query->where('name', 'Essay');
            })->doesntHave('uni_shortlisted')->where('student_id', $id)->get();

            $lor_doesnt_have_uni = Medias::select(['id', 'med_title', 'med_desc', 'med_file_path', 'med_file_name', 'med_file_format'])->
                with('uni_shortlisted:id,imported_id,uni_name,uni_major')->
                whereHas('media_categories', function($query) {
                    return $query->where('name', 'Letter of Recommendation');
            })->doesntHave('uni_shortlisted')->where('student_id', $id)->get();
        }

        $media['essay'] = ($show_item != "all") ? $essay_has_uni->merge($essay_doesnt_have_uni) : $essay_has_uni;
        $media['lor'] = ($show_item != "all") ? $lor_has_uni->merge($lor_doesnt_have_uni) : $lor_has_uni;

        $media['transcript'] = Medias::select(['id', 'med_title', 'med_desc', 'med_file_path', 'med_file_name', 'med_file_format'])->
            with('uni_shortlisted:id,imported_id,uni_name,uni_major')->
            whereHas('media_categories', function($query) {
                return $query->where('name', 'Transcript');
        })->where('student_id', $id)->get()->makeHidden('pivot');
                
        return response()->json(['success' => true, 'data' => $media]);
    }

    //** for university requirement / academic requirement  */
    public function list_of_uni_in_files ($file_category)
    {
        if (!$media_category = MediaCategory::where('name', 'like', '%'.$file_category.'%')->first()) {
            return response()->json(['success' => false, 'error' => 'Please make sure to submit only (essay, letter of recommendation, and transcript)']);
        }

        $med_cat_id = $media_category->id;
        //* select all media where media category id is $med_cat_id
        $medias = Medias::where('student_id', $this->student_id)->where('med_cat_id', $med_cat_id)->orderBy('created_at', 'desc')->get();
        
        //* select all universities that shortlisted for the $this->student_id
        $uni_shortlisted = UniShortlisted::where('student_id', $this->student_id)->orderBy('uni_name', 'desc')->get();
        
    }

    public function store_academic_requirement (Request $request)
    {
        
        $rules = [
            'student_id' => 'required|exists:students,id',
            'category' => 'required|in:sat,publication_links,ielts,toefl,ap_score',
            'subject.*' => 'required|string|max:255',
            'value.*' => ['required']
        ];

        if ($request->category == "publication_links") {
            $rules['value.*'][] = 'url';
        }

        $validator = Validator::make($request->all() + array('student_id' => $this->student_id), $rules);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'error' => $validator->errors()], 400);
        }

        DB::beginTransaction();
        try {

            for ($i = 0; $i < count($request->subject) ; $i++) {

                //* kalau ada data academic requirement dengan subject dan student id yg sama maka update
                if ($academic_req = AcademicRequirement::where('category', $request->category)->where('subject', $request->subject[$i])->where('student_id', $this->student_id)->first()) {
                    $academic_req->subject = $request->subject[$i];
                    $academic_req->value = $request->value[$i];
                    $academic_req->save();
                    continue;
                } 
                
                $academic_req = new AcademicRequirement;
                $academic_req->student_id = $this->student_id;
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

        switch($request->category) {
            case "sat":
                $category = 'SAT score';
                break;
            case "publication_links":
                $category = 'Publication link';
                break;
            case "ielts":
                $category = 'IELTS score';
                break;
            case "toefl":
                $category = 'TOEFL score';
                break;
            case "ap_score":
                $category = 'AP score';
                break;
            default:
                $category = 'Uni requirement';
        }

        return response()->json(['success' => true, 'message' => $category.' has been added']);
    }

    public function delete_academic_requirement ($academic_id) 
    {
        if (!$academic = AcademicRequirement::find($academic_id)) {
            return response()->json(['success' => false, 'error' => 'Couldn\'t find the Academic Report']);
        }

        DB::beginTransaction();
        try {
            $academic->delete();

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Delete Academic Report Issue : '.$e->getMessage());
            return response()->json(['success' => false, 'error' => 'Failed to delete academic report. Please try again.']);
        }

        switch($academic->category) {
            case "sat":
                $category = 'SAT score';
                break;
            case "publication_links":
                $category = 'Publication link';
                break;
            case "ielts":
                $category = 'IELTS score';
                break;
            case "toefl":
                $category = 'TOEFL score';
                break;
            case "ap_score":
                $category = 'AP score';
                break;
            default:
                $category = 'Uni requirement';
        }

        return response()->json(['success' => true, 'message' => $category.' has been deleted']);
    }

    public function store_document_requirement (Request $request)
    {
        
        $helper = new HelperController;
        $rules = [
            'student_id' => 'required|exists:students,id',
            //* validate if session student has 'uni_id' in the shortlisted
            'uni_id' => ['nullable', Rule::exists(UniShortlisted::class, 'imported_id')->where(function ($query) {
                $query->where('student_id', $this->student_id);
            })],
            'name' => 'required|max:255',
            'file_category' => 'required|in:essay,letter_of_recommendation,transcript',
            // 'subject' => 'required|string|max:255',
            'uploaded_file' => 'required|mimes:doc,docx,pdf,jpg,jpeg,png,webp|max:2048'
        ];

        $custom_msg = [
            'uploaded_file.max' => 'The file need to be less equal than 2Mb'
        ];

        $validator = Validator::make($request->all() + array('student_id' => $this->student_id), $rules, $custom_msg);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'error' => $validator->errors()], 400);
        }

        $category = MediaCategory::where('name', 'like', '%'.$helper->perfect_sentence($request->file_category).'%')->first();
        
        if ($request->uni_id != null) {
            // do this if there are uni shortlisted
            DB::beginTransaction();
            try {   

                //after upload to media succeed then submit to uni requirement
                $request->request->add([
                    'category' => $category->id,
                    'student_id' => $this->student_id,
                    'title' => $request->name,
                    'desc' => $request->name,  //! sementara masih pakai name, kedepannya dibuatin textbox utk description
                    'status' => 'verified'
                ]);
                $response = $this->upload_media($request);
                if ($response->getData()->success == false) {
                    return $response;
                }
                
                $inserted_media_id = $response->getData()->data->id;

                $media = Medias::find($inserted_media_id);
                //if the media_id was not pair with uni_id then attach
                if (!$media->uni_shortlisted()->where('imported_id', $request->uni_id)->first()) {
                    $uni = UniShortlisted::where('imported_id', $request->uni_id)->first();
                    $media->uni_shortlisted()->attach($uni->id, ['created_at' => Carbon::now(), 'updated_at' => Carbon::now()]);
                }

                DB::commit();
            } catch (Exception $e) {

                DB::rollBack();
                Log::error('Upload '.$helper->perfect_sentence($request->file_category).' Document Requirement Issue : '.$e->getMessage());
                return response()->json(['success' => false, 'error' => 'Failed to upload '.$helper->perfect_sentence($request->file_category).'. Please try again.']);
            }

            $uni_shortlisted = UniShortlisted::where('imported_id', $request->uni_id)->first();
            $uni_name = $uni_shortlisted->uni_name;
            return response()->json(['success' => true, 'message' => 'The '.$helper->perfect_sentence($request->file_category).' has been submitted for '.$uni_name]);
        } else {
            // do this if there are no uni shortlisted
            DB::beginTransaction();
            try {

                $request->request->add([
                    'category' => $category->id,
                    'student_id' => $this->student_id,
                    'title' => $request->name,
                    'desc' => $request->name,  //! sementara masih pakai name, kedepannya dibuatin textbox utk description
                    'status' => 'verified'
                ]);
                $response = $this->upload_media($request);
                if ($response->getData()->success == false) {
                    return $response;
                }
                
                DB::commit();
            } catch (Exception $e) {
                DB::rollBack();
                Log::error('Upload '.$helper->perfect_sentence($request->file_category).' Document Requirement Issue : '.$e->getMessage());
                return response()->json(['success' => false, 'error' => 'Failed to upload '.$helper->perfect_sentence($request->file_category).'. Please try again.']);
            }

            return response()->json(['success' => true, 'message' => 'The '.$helper->perfect_sentence($request->file_category).' has been uploaded.']);
        }

        
    }

    //** for university requirement / academic requirement end */
}
