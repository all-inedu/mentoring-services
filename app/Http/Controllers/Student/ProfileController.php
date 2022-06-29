<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Students;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\File;

class ProfileController extends Controller
{
    protected $student_id;

    public function __construct()
    {
        $this->student_id = auth()->guard('student-api')->user()->id;  
    }

    public function change_profile_picture(Request $request)
    {
        $rules = [
            'uploaded_file' => 'required|mimes:jpg,png|max:2048'
        ];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'error' => $validator->errors()], 400);
        }

        DB::beginTransaction();
        try {
            $profile = Students::find($this->student_id);
            $old_image_path = $profile->image;
            $file_path = substr($old_image_path, 7); // remove "public"

            if ($request->hasFile('uploaded_file')) {
                
                // check the old profile picture
                // if exist do delete;
                
                $isExists = File::exists(public_path($file_path));
                // dd($isExists);
                if ($isExists) {
                    File::delete(public_path($file_path));
                } else {
                    throw new Exception("Cannot find the file or the file does not exists");
                }

                $med_file_name = date('Ymd_His').'_profile-picture';
                $med_file_format = $request->file('uploaded_file')->getClientOriginalExtension();
                // $med_file_path = $request->file('uploaded_file')->storeAs($this->STUDENT_STORE_MEDIA_PATH.'/'.$request->student_id, $med_file_name.'.'.$med_file_format);
                $med_file_path = $request->file('uploaded_file')->storeAs($this->student_id, $med_file_name.'.'.$med_file_format, ['disk' => 'student_files']);

                $profile->image = 'public/media/'.$med_file_path;
                $profile->save();
            }

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Change Profile Picture Issue : ['.json_encode($this->student_id).'] '.$e->getMessage());
            return response()->json(['success' => false, 'error' => 'Failed to change profile picture. Please try again.']);
        }

        return response()->json(['success' => true, 'message' => 'Profile picture updated', 'data' => auth()->guard('student-api')->user()->fresh()]);
    }

    public function change_password(Request $request)
    {
        $rules = [
            'old_password' => 'required|password',
            'new_password' => 'required|string|min:6|confirmed|different:old_password'
        ];

        $custom_messages = [
            'new_password.different' => 'The new password must be different from your current password'
        ];

        $validator = Validator::make($request->all(), $rules, $custom_messages);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'error' => $validator->errors()], 400);
        }

        DB::beginTransaction();
        try {
            $student = Students::find($this->student_id);
            $student->password = Hash::make($request->new_password);
            $student->save();

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Change Students Password Issue : ['.json_encode($this->student_id).'] '.$e->getMessage());
            return response()->json(['success' => false, 'error' => 'Failed to change student password. Please try again.']);
        }

        return response()->json(['success' => true, 'message' => 'Your password has been changed']);
    }

    public function update(Request $request)
    {
        $rules = [
            'first_name'   => 'required|string|max:255',
            'last_name'    => 'required|string|max:255',
            'birthday'     => 'nullable|date',
            'phone_number' => 'required|string',
            'grade'        => 'nullable|integer|min:7',
            'address'      => 'required'
        ];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'error' => $validator->errors()], 400);
        }

        DB::beginTransaction();
        try {
            $user = Students::find($this->student_id);
            $user->first_name = $request->first_name;
            $user->last_name = $request->last_name;
            // $user->birthday = $request->birthday;
            $user->phone_number = $request->phone_number;
            $user->address = $request->address;
            // $user->grade = $request->grade;
            $user->save();

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Update Student Profile Issue : ['.json_encode($this->student_id).'] '.$e->getMessage());
            return response()->json(['success' => false, 'error' => 'Failed to update student profile. Please try again.']);
        }

        return response()->json(['success' => true, 'message' => 'Profile has been updated', 'data' => auth()->guard('student-api')->user()->fresh()]);
    }
}
