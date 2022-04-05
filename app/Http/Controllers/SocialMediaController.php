<?php

namespace App\Http\Controllers;

use App\Models\SocialMedia;
use App\Rules\PersonChecking;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SocialMediaController extends Controller
{

    public function index ($person, $id)
    {
        $rules = [
            'person' => 'required|in:user,student',
            'id' => [
                'required',
                new PersonChecking($person)
            ]
        ];

        $validator = Validator::make(['person' => $person, 'id' => $id], $rules);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'error' => $validator->errors()], 400);
        }

        try {
            $data = SocialMedia::when($person == "user", function($query) use ($id) {
                $query->where('user_id', $id);
            }, function($query) use ($id) {
                $query->where('student_id', $id);
            })->orderBy('created_at', 'desc')->get();
            
        } catch (Exception $e) {
            Log::error('Get List of Social Media Issue : ['.json_encode($data).'] '.$e->getMessage());
            return response()->json(['success' => false, 'error' => 'Failed to get list of social media. Please try again.']);
        }

        return response()->json(['success' => true, 'data' => $data]);
    }

    public function update($soc_med_id, Request $request) {

        try {
            $social_media = SocialMedia::findOrFail($soc_med_id);
        } catch (Exception $e) {
            Log::error('Find Social Media by Id Issue : ['.$soc_med_id.'] '.$e->getMessage());
            return response()->json(['success' => false, 'error' => 'Failed to find social media by Id. Please try again.']);
        }

        $rules = [
            'person' => 'required|in:user,student',
            'id' => [
                'required',
                new PersonChecking($request->person)
            ],
            'social_media_name' => 'required|unique:social_media,social_media_name,'.$soc_med_id.'|in:linkedin,facebook,instagram',
            'hyperlink' => 'required',
            'status' => 'nullable'
        ];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'error' => $validator->errors()], 400);
        }

        //insert to schedule table
        try {
            if ($request->person == "user") {
                $social_media->user_id = $request->id;
            } else if ($request->person == "student") {
                $social_media->student_id = $request->id;
            }
            $social_media->social_media_name = $request->social_media_name;
            $social_media->hyperlink = $request->hyperlink;
            $social_media->status = isset($request->status) ? $request->status : 1;
            $social_media->save();

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Update Social Media Issue : ['.json_encode($social_media).'] '.$e->getMessage());
            return response()->json(['success' => false, 'error' => 'Failed to update social media. Please try again.']);
        }

        return response()->json(['success' => true, 'message' => 'Social media has been updated', 'data' => $social_media]);
    }
    
    public function store(Request $request)
    {
        $rules = [
            'person' => 'required|in:user,student',
            'id' => [
                'required',
                new PersonChecking($request->person)
            ],
            'social_media_name' => 'required|unique:social_media,social_media_name|in:linkedin,facebook,instagram',
            'hyperlink' => 'required',
            'status' => 'nullable'
        ];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'error' => $validator->errors()], 401);
        }

        DB::beginTransaction();
        try {
            $social_media = new SocialMedia;
            if ($request->person == "user") {
                $social_media->user_id = $request->id;
            } else if ($request->person == "student") {
                $social_media->student_id = $request->id;
            }
            $social_media->social_media_name = $request->social_media_name;
            $social_media->hyperlink = $request->hyperlink;
            $social_media->status = isset($request->status) ? $request->status : 1;
            $social_media->save();
            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Add Social Media Issue : ['.json_encode($request->all()).'] '.$e->getMessage());
            return response()->json(['success' => false, 'error' => 'Failed to add social media to '.$request->person.'. Please try again.']);
        }

        return response()->json(['success' => true, 'message' => 'Social media has been added to '.$request->person, 'data' => $social_media]);
    }

    public function delete ($soc_med_id)
    {
        //Validation
        if (!$social_media = SocialMedia::find($soc_med_id)) {
            return response()->json(['success' => false, 'error' => 'Failed to find existing social media'], 400);
        } 

        DB::beginTransaction();
        try {
            $social_media->delete();
            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Delete Social Media Issue : ['.$soc_med_id.'] '.$e->getMessage());
            return response()->json(['success' => false, 'error' => 'Failed to delete social media. Please try again.'], 400);
        }
        return response()->json(['success' => true, 'message' => 'You\'ve successfully deleted social media']);
    }
}
