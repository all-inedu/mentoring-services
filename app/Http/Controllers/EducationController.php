<?php

namespace App\Http\Controllers;

use App\Models\Education;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Exception;
use Illuminate\Support\Facades\Log;

class EducationController extends Controller
{

    public function find ($edu_id)
    {
        try {
            $education = Education::findOrFail($edu_id);
        } catch (Exception $e) {
            Log::error('Find Education by Id Issue : ['.$edu_id.'] '.$e->getMessage());
            return response()->json(['success' => false, 'error' => 'Failed to find education by Id. Please try again.']);
        }
        return response()->json(['success' => true, 'data' => $education]);
    }

    public function select($user_id)
    {
        try {
            $education = Education::where('user_id', $user_id)->orderBy('created_at', 'desc')->get();
        } catch (Exception $e) {
            Log::error('Select Education Use User Id  Issue : ['.$user_id.'] '.$e->getMessage());
            return response()->json(['success' => false, 'error' => 'Failed to select education by user Id. Please try again.']);
        }
        return response()->json(['success' => true, 'data' => $education]);
    }

    public function delete($edu_id)
    {
        //Validation
        if (!$education = Education::find($edu_id)) {
            return response()->json(['success' => false, 'error' => 'Failed to find existing education'], 400);
        } 

        DB::beginTransaction();
        try {
            $education->delete();
            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Delete Education Issue : ['.$edu_id.'] '.$e->getMessage());
            return response()->json(['success' => false, 'error' => 'Failed to delete education. Please try again.'], 400);
        }
        return response()->json(['success' => true, 'message' => 'Education has been removed']);
    }

    public function update($edu_id, Request $request)
    {
        try {
            $education = Education::findOrFail($edu_id);
        } catch (Exception $e) {
            Log::error('Find Education by Id Issue : ['.$edu_id.'] '.$e->getMessage());
            return response()->json(['success' => false, 'error' => 'Failed to find education by Id. Please try again.']);
        }

        $rules = [
            'user_id'        => 'required|exists:users,id',
            'graduated_from' => 'required',
            'major'          => 'required',
            'degree'         => 'required'
        ];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'error' => $validator->errors()], 400);
        }

        DB::beginTransaction();
        try {   
            $user = User::find($request->user_id);
            $full_name = $user->first_name.' '.$user->last_name;

            $education->user_id = $request->user_id;
            $education->graduated_from = $request->graduated_from;
            $education->major = $request->major;
            $education->degree = $request->degree;
            $education->save();
            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Edit User Education Issue : ['.json_encode($request->all()).'] '.$e->getMessage());
            return response()->json(['success' => false, 'error' => 'Failed to edit user education to '.$full_name.'. Please try again.']);

        }

        return response()->json(['success' => true, 'message' => 'Education has been edited to '.$full_name, 'data' => $education]);
    }
    
    public function store(Request $request)
    {
        $rules = [
            'user_id'        => 'required|exists:users,id',
            'graduated_from' => 'required',
            'major'          => 'required',
            'degree'         => 'required'
        ];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'error' => $validator->errors()], 400);
        }

        DB::beginTransaction();
        try {   
            $user = User::find($request->user_id);
            $full_name = $user->first_name.' '.$user->last_name;

            $education = new Education;
            $education->user_id = $request->user_id;
            $education->graduated_from = $request->graduated_from;
            $education->major = $request->major;
            $education->degree = $request->degree;
            $education->save();
            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Add User Education Issue : ['.json_encode($request->all()).'] '.$e->getMessage());
            return response()->json(['success' => false, 'error' => 'Failed to add user education to '.$full_name.'. Please try again.']);

        }

        return response()->json(['success' => true, 'message' => 'Education has been added to '.$full_name, 'data' => $education]);
    }
}
