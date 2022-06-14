<?php

namespace App\Http\Traits;

use Illuminate\Http\Request;
use App\Models\MediaCategory;
use App\Rules\MediaTermsChecker;
use Illuminate\Support\Facades\Validator;
use App\Models\Medias;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Exception;
use Illuminate\Database\QueryException;

trait UploadMediaTrait 
{

    public function upload_media (Request $request)
    {
        //* validate category
        if (!MediaCategory::find($request->category)) {
            return response()->json(['success' => false, 'error' => 'Couldn\'t find category. Please try another id or try to submitting again']);
        }

        $rules = [
            'student_id' => 'required|exists:students,id',
            'title' => 'required|string|max:255',
            'desc' => 'required',
            'uploaded_file' => ['required','file','max:3000'],
            'status' => ['in:not-verified,verified', new MediaTermsChecker($request->category)]
        ];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'error' => $validator->errors()], 400);
        }

        // DB::beginTransaction();
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
            // DB::commit();
        } catch (Exception $e) {
            // DB::rollback();
            Log::error('Upload Student Media Issue : ['.json_encode($request->all()).'] '.$e->getMessage());
            return response()->json(['success' => false, 'error' => 'Failed to upload student media. Please try again.']);
        }

        return response()->json(['success' => true, 'message' => 'File has been uploaded', 'data' => $media]);
    }
}