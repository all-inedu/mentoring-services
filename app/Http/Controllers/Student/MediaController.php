<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\MediaCategory;
use App\Models\Medias;
use App\Providers\RouteServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use App\Models\Students;
use App\Rules\MediaTermsChecker;
use App\Models\UniShortlisted;
use App\Rules\MediaPairChecker;
use Carbon\Carbon;

class MediaController extends Controller
{

    protected $student_id;
    protected $STUDENT_STORE_MEDIA_PATH;
    protected $STUDENT_LIST_MEDIA_VIEW_PER_PAGE;

    public function __construct()
    {
        $this->student_id = Auth::guard('student-api')->user()->id;
        $this->STUDENT_STORE_MEDIA_PATH = RouteServiceProvider::STUDENT_STORE_MEDIA_PATH;
        $this->STUDENT_LIST_MEDIA_VIEW_PER_PAGE = RouteServiceProvider::STUDENT_LIST_MEDIA_VIEW_PER_PAGE;
    }
    

    // update media that been attached to university shortlisted
    public function pair_one_to_one (Request $request) 
    {
        $success = 0;
        $essay_med_id = $request->essay_med_id;
        $lor_media_id = $request->lor_med_id;

        $rules = [
            'pair' => 'nullable|boolean',
            'student_id' => 'required|exists:students,id',
            'category' => 'required|in:essay,lor',
            'essay_med_id' => [
                'nullable', 'required_if:category,essay', 
                'unique:uni_shortlisteds,essay_med_id', 'unique:uni_shortlisteds,lor_med_id', 
                Rule::exists(Medias::class, 'id')->where(function ($query) {
                    $query->where('student_id', $this->student_id);
                })],
            'lor_med_id' => [
                'nullable', 'required_if:category,lor', 
                'unique:uni_shortlisteds,lor_med_id', 'unique:uni_shortlisteds,essay_med_id',
                Rule::exists(Medias::class, 'id')->where(function ($query) {
                    $query->where('student_id', $this->student_id);
                })],
            'uni_id' => ['nullable', Rule::exists(UniShortlisted::class, 'imported_id')->where(function ($query) {
                $query->where('student_id', $this->student_id);
            })],
            'change_name' => 'nullable|boolean',
            'file_name' => 'required_if:change_name,true'
        ];

        $validator = Validator::make($request->all() + array('student_id' => $this->student_id), $rules);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'error' => $validator->errors()], 400);
        }
        
        // change name function
        if ($request->change_name == true) {

            $med_id = ($request->essay_med_id != "") ? $request->essay_med_id : $request->lor_med_id;
            $media = Medias::find($med_id);
            $media_name = $media->med_title;

            // matching the name
            // if media name different from request filename then update
            if ($media_name != $request->file_name) {
                $media->med_title = $request->file_name;
                $media->save();
            }
        }

        DB::beginTransaction();
        try {
            $uni_doc = UniShortlisted::where('imported_id', $request->uni_id)->first();
            if ($essay_med_id != "")
                $uni_doc->essay_med_id = $essay_med_id;

            if ($lor_media_id != "")
                $uni_doc->lor_med_id = $lor_media_id;

            $uni_doc->save();

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Pair Media File Issue : ['.json_encode($request->all() + array('student_id' => $this->student_id )).'] '.$e->getMessage());
            return response()->json(['success' => false, 'error' => 'Failed to pair media file. Please try again.']);
        }

        return response()->json(['success' => true]);
    }

    public function update (Request $request)
    {
        $rules = [
            'student_id' => 'required|exists:students,id',
            'media_id' => ['required',
                Rule::exists(Medias::class, 'id')->where(function ($query) {
                    $query->where('student_id', $this->student_id);
                })],
            'name' => 'required|regex:/^[A-Za-z0-9 ]+$/|max:255',
        ];

        $validator = Validator::make($request->all() + array('student_id' => $this->student_id), $rules);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'error' => $validator->errors()], 400);
        }

        $media = Medias::find($request->media_id);

        DB::beginTransaction();
        if ($media->med_title != $request->name) {
            // if media title from database is not equal with request name
            // then update the name
            try {

                $media->med_title = $request->name;
                $media->med_desc = $request->name;
                $media->save();
                DB::commit();
            } catch (Exception $e) {
                DB::rollBack();
                Log::error('Update Media File Issue : ['.json_encode($request->all() + array('student_id' => $this->student_id )).'] '.$e->getMessage());
                return response()->json(['success' => false, 'error' => 'Failed to update media file. Please try again.']);
            }

            return response()->json(['success' => true, 'message' => 'The filename has been changed']);
        }
   
    }

    public function pair (Request $request)
    {  
        $rules = [
            'general' => 'required|boolean',
            'student_id' => 'required|exists:students,id',
            'media_id' => ['required',
                Rule::exists(Medias::class, 'id')->where(function ($query) {
                    $query->where('student_id', $this->student_id);
                })],
            'name' => 'required|regex:/^[A-Za-z0-9 ]+$/|max:255',
            'uni_id' => ['nullable', Rule::exists(UniShortlisted::class, 'imported_id')->where(function ($query) {
                $query->where('student_id', $this->student_id);
            })],
        ];

        DB::beginTransaction();
        // checking media name id
        // if media name changed, then it should update 
        if ($media = Medias::find($request->media_id)) {
            if ($media->med_title != $request->name) {
                // if media title from database is not equal with request name
                // then update the name
                $media->med_title = $request->name;
                $media->med_desc = $request->name;
                $media->save();

                
                if ($media->uni_shortlisted()->where('imported_id', $request->uni_id)->first()) {
                    return response()->json(['success' => true, 'message' => 'The filename has been changed']);
                }
            }
        }

        $general = $request->general;
        if ($general == false) {
            // periksa apakah file tsb sudah ada di table uni requirement media
            if ($media->uni_shortlisted()->count() > 0) {
                // jika ada periksa kembali
                // apakah uni id yg diinput sama dengan yg tercatat di table uni requirement media
                if ($media->uni_shortlisted()->where('imported_id', $request->uni_id)->count() == 0) {
                    // jika uni id yang diinput berbeda maka dettach yang lama 
                    $media->uni_shortlisted()->detach(['uni_shortlisted_id' => $media->uni_shortlisted()->first()->pivot->uni_shortlisted_id]);
                }
                $rules['media_id'][] = 'unique:uni_requirement_media,med_id,'.$request->media_id;
                // $rules['media_id'][] = Rule::unique('uni_requirement_media', 'med_id')->ignore($request->media_id, 'med_id');
            }
        }

        $validator = Validator::make($request->all() + array('student_id' => $this->student_id), $rules);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'error' => $validator->errors()], 400);
        }
   
        
        try {
            // general means file tidak mengikat ke uni manapun.
            // general = true adalah file hanya ada di medias
            // general = false adalah file akan di  pair ke suatu uni
            switch ($request->general) {
                case true:
                    if ($media->uni_shortlisted()->count() > 0)
                        $media->uni_shortlisted()->detach(['uni_shortlisted_id' => $media->uni_shortlisted()->first()->pivot->uni_shortlisted_id]);
                    // $university->medias()->detach($request->media_id);
                    break;

                case false:
                    if (!$university = UniShortlisted::where('imported_id', $request->uni_id)->where('student_id', $this->student_id)->first()) {
                        return response()->json(['success' => false, 'error' => 'Couldn\'t find the university']);
                    }

                    $university->medias()->attach($request->media_id, [
                                    'created_at' => Carbon::now(),
                                    'updated_at' => Carbon::now(),
                                ]);
                    break;
            }

            
            
            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Pair Media File Issue : ['.json_encode($request->all() + array('student_id' => $this->student_id )).'] '.$e->getMessage());
            return response()->json(['success' => false, 'error' => 'Failed to pair media file. Please try again.']);
        }

        $media_category_name = $media->media_categories->name;

        return response()->json([
            'success' => true, 
            'message' => $request->general == false ? 
                'The '.$media_category_name.' of yours has successfully submitted to '.$university->uni_name
                : 'The submitted '.$media_category_name.' has successfully dettach']);
    }

    public function switch ($file_id, Request $request)
    {
        $student_id = $request->student_id;
        if (!Students::find($student_id)) {
            return response()->json(['success' => false, 'error' => 'Student ID is not exist']);
        }

        $rules = [
            'file_id' => [
                'required', Rule::exists(Medias::class, 'id')->where(function ($query) use ($student_id) {
                    $query->where('student_id', $student_id);
                }),
            ],
            'status' => 'required|in:verified,not-verified'
        ];

        $custom_message = [
            'status.in' => 'Status must be verified or not-verified'
        ];

        $validator = Validator::make($request->all() + ['file_id' => $file_id], $rules, $custom_message);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'error' => $validator->errors()], 400);
        }

        DB::beginTransaction();

        try {
            $media = Medias::find($file_id);
            $media->status = $request->status;
            $media->save();
            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Switch Status File Issue : ['.$file_id.', '.$request->status.'] '.$e->getMessage());
            return response()->json(['success' => false, 'error' => 'Failed to switch status file. Please try again.']);
        }

        return response()->json(['success' => true, 'message' => 'File has been changed to : '.$request->status]);
    }

    public function index(Request $request)
    {
        $use_keyword = $request->get('keyword') ? true : false;
        $keyword = !empty($request->get('keyword')) ? $request->get('keyword') : null;

        $student_email = $request->get('mail');
        if ($student_email) {
            $media = Medias::with('students', 'media_categories')->whereHas('students', function ($query) use ($student_email) {
                $query->where('email', $student_email);
            })->when($use_keyword, function($query) use ($keyword) {
                $query->where('med_title', 'like', '%'.$keyword.'%');
            })->orderBy('created_at', 'desc')->paginate($this->STUDENT_LIST_MEDIA_VIEW_PER_PAGE);
        } else {
            $media = Medias::with('students', 'media_categories')->
                when($use_keyword, function($query) use ($keyword) {
                    $query->where('med_title', 'like', '%'.$keyword.'%');
                })->orderBy('created_at', 'desc')->paginate($this->STUDENT_LIST_MEDIA_VIEW_PER_PAGE);
        }

        return response()->json(['success' => true, 'data' => $media]);
    }

    public function index_by_student(Request $request)
    {
        $use_keyword = $request->get('keyword') ? true : false;
        $keyword = !empty($request->get('keyword')) ? $request->get('keyword') : null;

        $student_id = auth()->guard('student-api')->user()->id;
        $media = Medias::with('media_categories')->whereHas('students', function ($query) use ($student_id) {
            $query->where('id', $student_id);
        })->when($use_keyword, function($query) use ($keyword) {
            $query->where('med_title', 'like', '%'.$keyword.'%');
        })->orderBy('created_at', 'desc')->paginate($this->STUDENT_LIST_MEDIA_VIEW_PER_PAGE);

        return response()->json(['success' => true, 'data' => $media]);
    }

    public function delete($media_id)
    {
        $media = Medias::where('id', $media_id)->where('student_id', $this->student_id)->first();
        if (!$media) {
            return response()->json(['success' => false, 'error' => 'Couldn\'t find the file']);
        }


        DB::beginTransaction();
        try {

            $media_file_path = $media->med_file_path;
            $file_path = substr($media_file_path, 7);
            
            $isExists = File::exists(public_path($file_path));
            // dd($isExists);
            if ($isExists) {
                File::delete(public_path($file_path));

                //delete record file from database
                $media->delete();
            } else {
                throw new Exception("Error occured");
            }

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Delete Student Media Issue : ['.$media_id.'] '.$e->getMessage());
            return response()->json(['success' => false, 'error' => 'Failed to delete student media. Please try again.']);
        }

        return response()->json(['success' => true, 'message' => 'File has been deleted.']);
    }
    
    public function store(Request $request)
    {
        //* validate category
        if (!$med_cat = MediaCategory::find($request->category)) {
            return response()->json(['success' => false, 'error' => 'Couldn\'t find category. Please try another or try again']);
        }

        $rules = [
            'student_id' => 'required|exists:students,id',
            'title' => 'required|string|max:255',
            'desc' => 'required',
            'uploaded_file' => ['required','file','max:3000'],
            'status' => ['in:not-verified,verified', new MediaTermsChecker($request->category)]
        ];

        // $med_cat = MediaCategory::where('id', $request->category)->first();
        // $med_cat_terms = $med_cat->terms == 'required' ? 'required' : 'nullable';
        // $med_cat_type = $med_cat->type == 'file' ? 'file' : 'string';

        // $rules['uploaded_file'] = [$med_cat_terms, $med_cat_type, 'max:3000'];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'error' => $validator->errors()], 400);
        }

        DB::beginTransaction();
        try {

            if ($request->hasFile('uploaded_file')) {
                $med_file_name = date('Ymd_His').'_'.str_replace(' ', '-', $request->title);
                $med_file_format = $request->file('uploaded_file')->getClientOriginalExtension();
                // $med_file_path = $request->file('uploaded_file')->storeAs($this->STUDENT_STORE_MEDIA_PATH.'/'.$request->student_id, $med_file_name.'.'.$med_file_format);
                $med_file_path = $request->file('uploaded_file')->storeAs($request->student_id, $med_file_name.'.'.$med_file_format, ['disk' => 'student_files']);

                $media = new Medias;
                $media->student_id = $request->student_id;
                $media->med_cat_id = $request->category;
                $media->med_title = $request->title;
                $media->med_desc = $request->desc;
                $media->med_file_path = /*public_path('media').*/'public/media/'.$med_file_path;
                $media->med_file_name = $med_file_name;
                $media->med_file_format = $med_file_format;
                $media->status = $request->status;
                $media->save();
            }
            DB::commit();

        } catch (Exception $e) {
            DB::rollback();
            Log::error('Upload Student Media Issue : ['.json_encode($request->all()).'] '.$e->getMessage());
            return response()->json(['success' => false, 'error' => 'Failed to upload student media. Please try again.']);
        }

        return response()->json(['success' => true, 'message' => 'File has been uploaded', 'data' => $media]);
    }
}
