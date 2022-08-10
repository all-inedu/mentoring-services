<?php

namespace App\Http\Controllers;

use App\Models\SocialMedia;
use App\Models\Students;
use App\Models\User;
use App\Providers\RouteServiceProvider;
use App\Rules\PersonChecking;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class SocialMediaController extends Controller
{

    protected $student_id;
    protected $user_id;

    public function __construct()
    {
        $this->student_id = Auth::guard('student-api')->check() ? Auth::guard('student-api')->user()->id : NULL;
        $this->user_id = Auth::guard('api')->check() ? Auth::guard('api')->user()->id : NULL;
    }

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
    
    public function store(Request $request)
    {   
        $id = $request->person == "student" ? $this->student_id : $this->user_id;

        $rules = [
            'person' => 'required|in:user,student',
            // 'id' => [
            //     'required',
            //     new PersonChecking($request->person)
            // ],
            'data.*.instance' => ['nullable', 'in:linkedin,facebook,instagram', 
                        // Rule::unique('social_media', 'social_media_name')->where(function ($query) use ($request, $id) {
                        //     return $query->when($request->person == "student", function($query1) use ($id) {
                        //         return $query1->where('student_id', $id);
                        //     }, function ($query2) use ($id) {
                        //         return $query2->where('user_id', $id);
                        //     });
                        // })
                    ],
            'data.*.hyperlink' => 'nullable|url',
            'data.*.username' => 'nullable',
            'data.*.status' => 'nullable'
        ];

        $validator = Validator::make($request->all() + array('id' => $id), $rules);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'error' => $validator->errors()], 401);
        }

        DB::beginTransaction();
        try {

            if ($request->person == "user") {
                $user = User::find($id);
            } else if ($request->person == "student") {
                $user = Students::find($id);
            }

            for ($i = 0; $i < count($request->data) ; $i++) {

                if ($result = $user->social_media()->where('social_media_name', 'like', '%'.$request->data[$i]['instance'].'%')->first()) {
                    
                    $user->social_media()->where('id', $result->id)->update(array(
                        'social_media_name' => $request->data[$i]['instance'],
                        'hyperlink' => $request->data[$i]['hyperlink'],
                        'username' => $request->data[$i]['username'],
                        'status' => isset($request->data[$i]['status']) ? $request->data[$i]['status'] : 1,
                        'created_at' => Carbon::now(),
                        'updated_at' => Carbon::now()
                    ));
                    continue;
                }

                // save requested data into variable request_data
                $request_data[$i] = array(
                    'social_media_name' => $request->data[$i]['instance'],
                    'hyperlink' => $request->data[$i]['hyperlink'],
                    'username' => $request->data[$i]['username'],
                    'status' => isset($request->data[$i]['status']) ? $request->data[$i]['status'] : 1,
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now()
                );
            }

            if (!empty($request_data))
                $user->social_media()->createMany($request_data);

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Add Social Media Issue : ['.json_encode($request->all()).'] '.$e->getMessage());
            return response()->json(['success' => false, 'error' => 'Failed to add social media to '.$request->person.'. Please try again.']);
        }

        return response()->json(['success' => true, 'message' => 'Social media has submitted to '.$request->person, 'data' => $user->social_media]);
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
