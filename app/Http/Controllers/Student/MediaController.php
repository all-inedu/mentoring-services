<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Medias;
use App\Providers\RouteServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Auth;

class MediaController extends Controller
{

    protected $STUDENT_STORE_MEDIA_PATH;
    protected $STUDENT_LIST_MEDIA_VIEW_PER_PAGE;

    public function __construct()
    {
        $this->STUDENT_STORE_MEDIA_PATH = RouteServiceProvider::STUDENT_STORE_MEDIA_PATH;
        $this->STUDENT_LIST_MEDIA_VIEW_PER_PAGE = RouteServiceProvider::STUDENT_LIST_MEDIA_VIEW_PER_PAGE;
    }

    public function index(Request $request)
    {
        $student_email = $request->get('mail');
        if ($student_email) {
            $media = Medias::whereHas('students', function ($query) use ($student_email) {
                $query->where('email', $student_email);
            })->orderBy('created_at', 'desc')->paginate($this->STUDENT_LIST_MEDIA_VIEW_PER_PAGE);
        } else {
            $media = Medias::orderBy('created_at', 'desc')->paginate($this->STUDENT_LIST_MEDIA_VIEW_PER_PAGE);
        }

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
        $rules = [
            'student_id' => 'required|exists:students,id',
            'category' => 'required|exists:media_categories,id',
            'title' => 'required|string|max:255',
            'desc' => 'required',
            'uploaded_file' => 'required|file|max:3000',
            'status' => 'required|in:not-verified,verified'
        ];

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
                $media->med_file_path = public_path('media').'/'.$med_file_path;
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
