<?php

namespace App\Http\Controllers;

use App\Models\SocialMedia;
use App\Models\Students;
use App\Models\User;
use App\Rules\PersonChecking;
use Carbon\Carbon;
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

    public function update(Request $request) 
    {

        $rules = [
            'person' => 'required|in:user,student',
            'id' => [
                'required',
                new PersonChecking($request->person)
            ],
            // 'instance_id' => 'sometimes|required|exists:social_media,id',
            'instance_id.*' => 'required|exists:social_media,id',
            // 'instance' => 'sometimes|required|in:linkedin,facebook,instagram',
            'instance.*' => 'required|in:linkedin,facebook,instagram',
            // 'hyperlink' => 'sometimes|required|url',
            'hyperlink.*' => 'required|url',
            // 'status' => 'nullable',
            'status.*' => 'nullable'
        ];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'error' => $validator->errors()], 400);
        }

        //insert to schedule table
        try {
            if ($request->person == "user") {
                $user = User::find($request->id);
            } else if ($request->person == "student") {
                $user = Students::find($request->id);
            }
            
            for ($i = 0; $i < count($request->instance) ; $i++) {
                // save requested data into variable request_data
                // $request_data[$i] = array(
                //     'social_media_name' => $request->instance[$i],
                //     'hyperlink' => $request->hyperlink[$i],
                //     'status' => isset($request->status[$i]) ? $request->status[$i] : 1,
                //     'created_at' => Carbon::now(),
                //     'updated_at' => Carbon::now()
                // );

                $user->social_media()->where('id', $request->instance_id[$i])->update(array(
                    'social_media_name' => $request->instance[$i],
                    'hyperlink' => $request->hyperlink[$i],
                    'status' => isset($request->status[$i]) ? $request->status[$i] : 1,
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now()
                ));
            }
            
            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Update Social Media Issue : ['.json_encode($user->social_media).'] '.$e->getMessage());
            return response()->json(['success' => false, 'error' => 'Failed to update social media. Please try again.']);
        }

        return response()->json(['success' => true, 'message' => 'Social media has been updated', 'data' => $user->social_media]);
    }
    
    public function store(Request $request)
    {
        $rules = [
            'person' => 'required|in:user,student',
            'id' => [
                'required',
                new PersonChecking($request->person)
            ],
            'instance' => 'required|in:linkedin,facebook,instagram',
            'instance.*' => 'required|in:linkedin,facebook,instagram',
            'hyperlink' => 'required|url',
            'hyperlink.*' => 'required|url',
            'status' => 'nullable',
            'status.*' => 'nullable'
        ];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'error' => $validator->errors()], 401);
        }

        DB::beginTransaction();
        try {

            if ($request->person == "user") {
                $user = User::find($request->id);
            } else if ($request->person == "student") {
                $user = Students::find($request->id);
            }

            for ($i = 0; $i < count($request->instance) ; $i++) {
                // save requested data into variable request_data
                $request_data[$i] = array(
                    'social_media_name' => $request->instance[$i],
                    'hyperlink' => $request->hyperlink[$i],
                    'status' => isset($request->status[$i]) ? $request->status[$i] : 1,
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now()
                );
            }

            $user->social_media()->createMany($request_data);

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Add Social Media Issue : ['.json_encode($request->all()).'] '.$e->getMessage());
            return response()->json(['success' => false, 'error' => 'Failed to add social media to '.$request->person.'. Please try again.']);
        }

        return response()->json(['success' => true, 'message' => 'Social media has been added to '.$request->person, 'data' => $user->social_media]);
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
