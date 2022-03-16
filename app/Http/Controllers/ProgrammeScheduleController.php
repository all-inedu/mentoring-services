<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Validator;
use Exception;
use App\Models\ProgrammeSchedules;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProgrammeScheduleController extends Controller
{

    public function select($prog_dtl_id)
    {
        try {
            $prog_schedule = ProgrammeSchedules::where('prog_dtl_id', $prog_dtl_id)->orderBy('created_at', 'desc')->get();
        } catch (Exception $e) {
            Log::error('Select Programme Schedule Use Programme Detail Id  Issue : ['.$prog_dtl_id.'] '.$e->getMessage());
            return response()->json(['success' => false, 'error' => 'Failed to select programme schedule by programme detail Id. Please try again.']);
        }
        return response()->json(['success' => true, 'data' => $prog_schedule]);
    }

    public function find($prog_sch_id)
    {
        try {
            $prog_schedule = ProgrammeSchedules::findOrFail($prog_sch_id);
        } catch (Exception $e) {
            Log::error('Find Programme Schedule by Id Issue : ['.$prog_sch_id.'] '.$e->getMessage());
            return response()->json(['success' => false, 'error' => 'Failed to find programme schedule by Id. Please try again.']);
        }
        return response()->json(['success' => true, 'data' => $prog_schedule]);
    }

    public function store(Request $request)
    {

        $rules = [
            'prog_dtl_id' => 'required|exists:programme_details,id',
            'prog_sch_start_date' => 'required|date_format:Y-m-d',
            'prog_sch_start_time' => [
                            'required',
                            'date_format:H:i', 
                            Rule::unique('programme_schedules')->where(function($query) use ($request) {
                                $query->where('prog_sch_start_date', $request->prog_sch_start_date);
                            })
                        ],
            'prog_sch_end_time'   => 'required|date_format:H:i|after:prog_sch_start_time'
        ];

        $custom_messages = [
            'prog_sch_start_date.required'    => 'The programme start date field is required',
            'prog_sch_start_date.date_format' => 'The programme start date does not match the format Y-m-d',
            'prog_sch_start_time.required'    => 'The programme start time field is required',
            'prog_sch_start_time.date_format' => 'The programme start time does not match the format H:i',
            'prog_sch_start_time.unique'      => 'The programme start time has already been taken',
            'prog_sch_end_time.required'      => 'The programme end time field is required',
            'prog_sch_end_time.date_format'   => 'The programme end time does not match the format H:i',
            'prog_sch_end_time.after'         => 'The programme end time must be a date after start time'
        ];

        if (isset($request->prog_sch_end_date)) {
            $rules['prog_sch_end_date'] = 'required|date_format:Y-m-d|after:prog_sch_start_date';
            $custom_messages['prog_sch_end_date.required'] = 'The programme end date field is required';
            $custom_messages['prog_sch_end_date.date_format'] = 'The programme end date does not match the format Y-m-d';
        }


        $validator = Validator::make($request->all(), $rules, $custom_messages);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'error' => $validator->errors()], 400);
        }

        //insert to schedule table
        try {
            $schedule = new ProgrammeSchedules;
            $schedule->prog_dtl_id = $request->prog_dtl_id;
            $schedule->prog_sch_start_date = $request->prog_sch_start_date;
            $schedule->prog_sch_start_time = $request->prog_sch_start_time;
            $schedule->prog_sch_end_date = $request->prog_sch_end_date;
            $schedule->prog_sch_end_time = $request->prog_sch_end_time;
            $schedule->save();

            $data['schedule'] = $schedule;

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Save Schedule Issue : ['.$request->prog_dtl_id.', '.json_encode($schedule).'] '.$e->getMessage());
            return response()->json(['success' => false, 'error' => 'Failed to save schedule. Please try again.']);
        }

        return response()->json(['success' => true, 'message' => 'Programme schedule has been made', 'data' => $data]);
    }

    public function update($prog_sch_id, Request $request)
    {

        try {
            $prog_schedule = ProgrammeSchedules::findOrFail($prog_sch_id);
        } catch (Exception $e) {
            Log::error('Find Program Schedule by Id Issue : ['.$prog_sch_id.'] '.$e->getMessage());
            return response()->json(['success' => false, 'error' => 'Failed to find programme schedule by Id. Please try again.']);
        }

        $rules = [
            'prog_sch_id' => 'required|exists:programme_schedules,id',
            'prog_dtl_id' => 'required|exists:programme_details,id',
            'prog_sch_start_date' => 'required|date_format:Y-m-d',
            'prog_sch_start_time' => [
                    'required',
                    'date_format:H:i', 
                    Rule::unique('programme_schedules')->where(function($query) use ($request) {
                        $query->where('prog_sch_start_date', $request->prog_sch_start_date);
                    })->ignore($prog_sch_id)
                ],
            'prog_sch_end_time'   => 'required|date_format:H:i|after:prog_sch_start_time'
        ];

        $custom_messages = [
            'prog_sch_start_date.required'    => 'The programme start date field is required',
            'prog_sch_start_date.date_format' => 'The programme start date does not match the format Y-m-d',
            'prog_sch_start_time.required'    => 'The programme start time field is required',
            'prog_sch_start_time.date_format' => 'The programme start time does not match the format H:i',
            'prog_sch_start_time.unique'      => 'The programme start time has already been taken',
            'prog_sch_end_time.required'      => 'The programme end time field is required',
            'prog_sch_end_time.date_format'   => 'The programme end time does not match the format H:i',
            'prog_sch_end_time.after'         => 'The programme end time must be a date after start time'
        ];

        if (isset($request->prog_sch_end_date)) {
            $rules['prog_sch_end_date'] = 'required|date_format:Y-m-d|after:prog_sch_start_date';
            $custom_messages['prog_sch_end_date.required'] = 'The programme end date field is required';
            $custom_messages['prog_sch_end_date.date_format'] = 'The programme end date does not match the format Y-m-d';
        }


        $validator = Validator::make($request->all() + ['prog_sch_id' => $prog_sch_id], $rules, $custom_messages);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'error' => $validator->errors()], 400);
        }

        try {
            $prog_schedule->prog_dtl_id = $request->prog_dtl_id;
            $prog_schedule->prog_sch_start_date = $request->prog_sch_start_date;
            $prog_schedule->prog_sch_start_time = $request->prog_sch_start_time;
            $prog_schedule->prog_sch_end_date = $request->prog_sch_end_date;
            $prog_schedule->prog_sch_end_time = $request->prog_sch_end_time;
            $prog_schedule->save();

            $data['schedule'] = $prog_schedule;

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Save Schedule Issue : ['.$request->prog_sch_id.', '.json_encode($prog_schedule).'] '.$e->getMessage());
            return response()->json(['success' => false, 'error' => 'Failed to save schedule. Please try again.']);
        }

        return response()->json(['success' => true, 'message' => 'Programme schedule has been made', 'data' => $data]);
    }

    public function delete($prog_sch_id)
    {
        //Validation
        if (!$prog_schedules = ProgrammeSchedules::find($prog_sch_id)) {
            return response()->json(['success' => false, 'error' => 'Failed to find existing programme schedule'], 400);
        } 

        DB::beginTransaction();
        try {
            $prog_schedules->delete();
            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Delete Programme Schedule Issue : ['.$prog_sch_id.'] '.$e->getMessage());
            return response()->json(['success' => false, 'error' => 'Failed to delete the programme schedule. Please try again.'], 400);
        }
        return response()->json(['success' => true, 'message' => 'You\'ve successfully deleted programme schedule']);
    }
}
