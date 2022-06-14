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

class MediaController extends Controller
{

    protected $STUDENT_STORE_MEDIA_PATH;
    protected $STUDENT_LIST_MEDIA_VIEW_PER_PAGE;

    public function __construct()
    {
        $this->STUDENT_STORE_MEDIA_PATH = RouteServiceProvider::STUDENT_STORE_MEDIA_PATH;
        $this->STUDENT_LIST_MEDIA_VIEW_PER_PAGE = RouteServiceProvider::STUDENT_LIST_MEDIA_VIEW_PER_PAGE;
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
        $media = Medias::findOrFail($media_id);
        if (!$media) {
            return response()->json(['success' => false, 'error' => 'Couldn\'t find the file']);
        }

        DB::beginTransaction();
        try {
            
            if (File::exists($media->med_file_path)) {
                File::delete($media->med_file_path);

                //delete record file from database
                if ($media->student_id == Auth::user()->id) {
                    $media->delete();
                }
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
