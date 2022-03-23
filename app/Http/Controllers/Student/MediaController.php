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

class MediaController extends Controller
{

    protected $STUDENT_STORE_MEDIA_PATH;

    public function __construct()
    {
        $this->STUDENT_STORE_MEDIA_PATH = RouteServiceProvider::STUDENT_STORE_MEDIA_PATH;
    }
    
    public function store(Request $request)
    {
        $rules = [
            'student_id' => 'required|exists:students,id',
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
                $med_file_path = $request->file('uploaded_file')->storeAs($this->STUDENT_STORE_MEDIA_PATH.'/'.encrypt($request->student_id), $med_file_name.'.'.$med_file_format);

                $media = new Medias;
                $media->student_id = $request->student_id;
                $media->med_title = $request->title;
                $media->med_desc = $request->desc;
                $media->med_file_path = $med_file_path;
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
