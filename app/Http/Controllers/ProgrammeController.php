<?php

namespace App\Http\Controllers;

use App\Models\Programmes;
use App\Models\ProgrammeSchedules;
use App\Providers\RouteServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;

class ProgrammeController extends Controller
{

    protected $store_media_path;

    public function __construct()
    {
        $this->store_media_path = RouteServiceProvider::USER_STORE_MEDIA_PATH;
    }
    
    public function select($prog_mod_id)
    {
        try {
            $programme = Programmes::with('programme_schedules')->where('prog_mod_id', $prog_mod_id)->orderBy('created_at', 'desc')->get();
        } catch (Exception $e) {
            Log::error('Select Programme Use Programme Module Issue : ['.$prog_mod_id.'] '.$e->getMessage());
            return response()->json(['success' => false, 'error' => 'Failed to select programme by programme module Id. Please try again.']);
        }
        return response()->json(['success' => true, 'data' => $programme]);
    }

    public function find($prog_id)
    {
        try {
            $programme = Programmes::findOrFail($prog_id);
        } catch (Exception $e) {
            Log::error('Find Programme by Id Issue : ['.$prog_id.'] '.$e->getMessage());
            return response()->json(['success' => false, 'error' => 'Failed to find programme by Id. Please try again.']);
        }
        return response()->json(['success' => true, 'data' => $programme]);
    }

    public function index()
    {
        $programme_modules = Programmes::orderBy('created_at', 'desc')->get();
        return response()->json(['succes' => true, 'data' => $programme_modules]);
    }

    public function store(Request $request)
    {
        //initialize variable
        $data = $prog_has = array();
        $programme_inserted_id = $med_file_name = $med_file_format = $med_file_path = NULL;

        //VALIDATION START
        $rules = [
            'prog_mod_id' => 'required|exists:programme_modules,id',
            'prog_name'   => 'required|string|max:255|unique:programmes,prog_name',
            'prog_desc'   => 'required',
            'status'      => 'required|in:active,deactive',
            'prog_price'  => 'nullable|integer',
            'prog_file'   => 'nullable|max:3000'
        ];

        $custom_messages = [
            'prog_mod_id.exists' => 'The selected programme modules is invalid',
        ];

        //* do this if programme has schedules
        if (in_array("schedule", $this->programme_has($request->prog_has))) {
            array_push($prog_has, "schedule");

            $add_rules = [
                'prog_schedule.prog_sch_start_date' => 'required|date_format:Y-m-d',
                'prog_schedule.prog_sch_start_time' => 'required|date_format:H:i',
                'prog_schedule.prog_sch_end_time'   => 'required|date_format:H:i|after:prog_schedule.prog_sch_start_time'
            ];

            $add_custom_messages = [
                'prog_schedule.prog_sch_start_date.required'    => 'The programme start date field is required',
                'prog_schedule.prog_sch_start_date.date_format' => 'The programme start date does not match the format Y-m-d',
                'prog_schedule.prog_sch_start_time.required'    => 'The programme start time field is required',
                'prog_schedule.prog_sch_start_time.date_format' => 'The programme start time does not match the format H:i',
                'prog_schedule.prog_sch_end_time.required'      => 'The programme end time field is required',
                'prog_schedule.prog_sch_end_time.date_format'   => 'The programme end time does not match the format H:i',
                'prog_schedule.prog_sch_end_time.after'         => 'The programme end time must be a date after start time'
            ];

            if (isset($request->prog_schedule['prog_sch_end_date'])) {
                $add_rules['prog_schedule.prog_sch_end_date'] = 'required|date_format:Y-m-d|after:prog_schedule.prog_sch_start_date';
                $add_custom_messages['prog_schedule.prog_sch_end_date.required'] = 'The programme end date field is required';
                $add_custom_messages['prog_schedule.prog_sch_end_date.date_format'] = 'The programme end date does not match the format Y-m-d';
            }

            $rules = array_merge($rules, $add_rules);
            $custom_messages = array_merge($custom_messages, $add_custom_messages);
            
        }
        //* end of programme has schedules

        //* do this if programme has link
        if (in_array("link", $this->programme_has($request->prog_has))) {
            array_push($prog_has, "link");
            
            $add_rules = [
                'prog_href' => 'required'
            ];

            $add_custom_messages = [
                'prog_href' => 'The link used for programme is required'
            ];

            $rules = array_merge($rules, $add_rules);
            $custom_messages = array_merge($custom_messages, $add_custom_messages);
        }
        //* end of programme has link

        $validator = Validator::make($request->all(), $rules, $custom_messages);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'error' => $validator->errors()], 400);
        }
        //VALIDATION END

        //begin process
        DB::beginTransaction();
        //insert to programme table
        try {

            if ($request->hasFile('prog_file')) {
                $med_file_name = date('Ymd_His').'_'.str_replace(' ', '-', $request->prog_name);
                $med_file_format = $request->file('prog_file')->getClientOriginalExtension();
                $med_file_path = $request->file('prog_file')->storeAs($this->store_media_path, $med_file_name.'.'.$med_file_format);
            }

            $programme = new Programmes;
            $programme->prog_mod_id = $request->prog_mod_id;
            $programme->prog_name = $request->prog_name;
            $programme->prog_desc = $request->prog_desc;
            $programme->prog_has = json_encode($prog_has);
            $programme->prog_href = isset($request->prog_href) ? $request->prog_href : NULL;
            $programme->prog_price = $request->prog_price;
            $programme->prog_file_path = $med_file_path;
            $programme->status = $request->status;
            $programme->save();
            $programme_inserted_id = $programme->id;

            $data['programme'] = $programme;

        } catch (Exception $e) {

            DB::rollBack();
            Log::error('Create Programme Issue : ['.$request->prog_mod_id.', '.$request->prog_name.', '.$request->prog_desc.', '.$request->status.'] '.$e->getMessage());
            return response()->json(['success' => false, 'error' => 'Failed to create a new programme. Please try again.']);
        }

        //insert to schedule table
        try {
            $schedule = new ProgrammeSchedules;
            $schedule->prog_id = $programme_inserted_id;
            $schedule->prog_sch_start_date = $request->prog_schedule['prog_sch_start_date'];
            $schedule->prog_sch_start_time = $request->prog_schedule['prog_sch_start_time'];
            $schedule->prog_sch_end_date = $request->prog_schedule['prog_sch_end_date'];
            $schedule->prog_sch_end_time = $request->prog_schedule['prog_sch_end_time'];
            $schedule->save();

            $data['schedule'] = $schedule;

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Save Schedule Issue : ['.$programme_inserted_id.', '.$request->prog_schedule.'] '.$e->getMessage());
            return response()->json(['success' => false, 'error' => 'Failed to save schedule. Please try again.']);
        }

        DB::commit();
        return response()->json(['success' => true, 'message' => 'New programme has been made', 'data' => $data]);
    }

    public function update($prog_id, Request $request)
    {
        try {
            $programme = Programmes::findOrFail($prog_id);
        } catch (Exception $e) {
            Log::error('Find Program by Id Issue : ['.$prog_id.'] '.$e->getMessage());
            return response()->json(['success' => false, 'error' => 'Failed to find programme module by Id. Please try again.']);
        }

        $rules = [
            'prog_mod_id' => 'required|exists:programme_modules,id',
            'prog_name'   => 'required|string|max:255',
            'prog_desc'   => 'required',
            'status'      => 'required|in:active,deactive'
        ];

        $custom_messages = [
            'prog_mod_id.exists' => 'The selected programme modules is invalid',
        ];

        $validator = Validator::make($request->all(), $rules, $custom_messages);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'error' => $validator->errors()], 400);
        }

        DB::beginTransaction();
        try {
            $programme->prog_mod_id = $request->prog_mod_id;
            $programme->prog_name = $request->prog_name;
            $programme->prog_desc = $request->prog_desc;
            $programme->status = $request->status;
            $programme->save();
            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Update Programme Issue : ['.$request->prog_mod_id.', '.$request->prog_name.', '.$request->prog_desc.', '.$request->status.'] '.$e->getMessage());
            return response()->json(['success' => false, 'error' => 'Failed to update programme. Please try again.']);
        }       

        return response()->json(['success' => true, 'message' => 'Programme module has been updated', 'data' => $programme]);
    }

    public function delete($prog_id)
    {
        //Validation
        if (!$programme = Programmes::find($prog_id)) {
            return response()->json(['success' => false, 'error' => 'Failed to find existing programme'], 400);
        } 

        DB::beginTransaction();
        try {
            $programme->delete();
            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Delete Programme Issue : ['.$prog_id.'] '.$e->getMessage());
            return response()->json(['success' => false, 'error' => 'Failed to delete the programme. Please try again.'], 400);
        }
        return response()->json(['success' => true, 'message' => 'You\'ve successfully deleted the programme']);
    }

    ////////////////////////////////////////////
    ////////////////////////////////////////////
    ////////////////////////////////////////////

    public function programme_has($string)
    {
        //remove square bracket
        $programme_has = str_replace(array('[',']'), '', $string);

        //remove "
        $programme_has = str_replace('"', '', $programme_has);

        //string to array
        $exp = explode(',', $programme_has);
        return $exp;
    }
}
