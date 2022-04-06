<?php

namespace App\Http\Controllers;

use App\Models\UserSchedule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Exception;
use Illuminate\Support\Facades\Log;
use App\Models\User;

class UserScheduleController extends Controller
{

    public function find($role_name, $user_sch_id)
    {
        try {
            $user_schedule = UserSchedule::whereHas('users', function ($query) use ($role_name) {
                $query->whereHas('roles', function ($query2) use ($role_name) {
                    $query2->where('role_name', strtolower($role_name));
                });
            })->where('id', $user_sch_id)->first();

            if (!$user_schedule) {
                return response()->json(['success' => false, 'error' => 'Couldn\'t find the schedule. Please recheck the parameters.' ]);
            }

        } catch (Exception $e) {
            Log::error('Find Program by Id Issue : ['.$user_sch_id.'] '.$e->getMessage());
            return response()->json(['success' => false, 'error' => 'Failed to find schedule by Id. Please try again.']);
        }
        return response()->json(['success' => true, 'data' => $user_schedule]);
    }
    
    public function store(Request $request)
    {
        $rules = [
            'user_id'         => 'exists:users,id',
            'us_days'         => 'required|string|in:monday,tuesday,wednesday,thursday,friday,saturday,sunday',
            'us_start_time.*' => 'required|date_format:H:i'
        ];

        if (isset($request->us_end_time)) {
            $rules['us_end_time.*'] = 'date_format:H:i';
        }

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'error' => $validator->errors()], 400);
        }

        $us_start_time = $request->us_start_time;
        $us_end_time = $request->us_end_time;

        DB::beginTransaction();
        try {
            for ($i = 0; $i < count($us_start_time); $i++) {
                $user_schedule = new UserSchedule;
                $user_schedule->user_id = $request->user_id;
                $user_schedule->us_days = $request->us_days;
                $user_schedule->us_start_time = $request->us_start_time[$i];
                $user_schedule->us_end_time = isset($request->us_end_time) ? $request->us_end_time[$i] : null;
                $user_schedule->save();
            }
            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Create Schedule Issue : ['.json_encode($request->all()).'] '.$e->getMessage());
            return response()->json(['success' => false, 'error' => 'Failed to create schedule. Please try again.'], 400);
        }
        return response()->json(['success' => true, 'message' => 'You\'ve successfully created schedule.']);
    }

    public function delete($schedule_id)
    {
        //Validation
        if (!$user_schedule = UserSchedule::find($schedule_id)) {
            return response()->json(['success' => false, 'error' => 'Failed to delete existing schedule.'], 400);
        } 

        DB::beginTransaction();
        try {
            $user_schedule->delete();
            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Delete Schedule Issue : ['.$schedule_id.'] '.$e->getMessage());
            return response()->json(['success' => false, 'error' => 'Failed to delete your schedule. Please try again.'], 400);
        }
        return response()->json(['success' => true, 'message' => 'You\'ve successfully deleted your schedule.']);
    }
}
