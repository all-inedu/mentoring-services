<?php

namespace App\Http\Controllers;

use App\Models\ProgrammeDetails;
use App\Models\ProgrammeModules;
use App\Models\Programmes;
use App\Models\ProgrammeSchedules;
use App\Models\StudentActivities;
use App\Providers\RouteServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;

class ProgrammeController extends Controller
{

    protected $store_media_path;
    protected $ADMIN_LIST_PROGRAMME_VIEW_PER_PAGE;

    public function __construct()
    {
        $this->store_media_path = RouteServiceProvider::USER_STORE_MEDIA_PATH;
        $this->ADMIN_LIST_PROGRAMME_VIEW_PER_PAGE = RouteServiceProvider::ADMIN_LIST_PROGRAMME_VIEW_PER_PAGE;
    }

    public function switch($status, Request $request)
    {
        $rules = [
            'prog_id' => 'required|exists:programmes,id',
            'status'   => 'in:active,inactive'
        ];

        $custom_message = [
            'prog_id.required' => 'Programme Id is required.',
            'prog_id.exists' => 'Programme Id is invalid'
        ];

        $validator = Validator::make($request->all() + ['status' => $status], $rules, $custom_message);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'error' => $validator->errors()], 400);
        }

        DB::beginTransaction();
        try {

            if (!$programme = Programmes::find($request->prog_id)) {
                return response()->json(['success' => false, 'error' => 'The programme does not exists']);
            }

            // if there are active programme on student activities then the status cannot be changed to inactive 
            if ( ($status == "inactive") && (count(StudentActivities::where('prog_id', $request->prog_id)->get()) > 0)) {
                return response()->json(['success' => false, 'error' => 'The programme is still in use.']);
            }

            $programme->status = $request->status;
            $programme->save();
            DB::commit();

        } catch (Exception $e) {

            DB::rollBack();
            Log::error('Switch status Issue : ['.$request->prog_id.', '.$status.'] '.$e->getMessage());
            return response()->json(['success' => false, 'error' => 'Failed to switch programme status. Please try again.']);
        }
        
        return response()->json(['success' => true, 'message' => 'The programme has been changed to '.$status]);
    }
    
    public function select($prog_mod_id)
    {
        try {
            $programme = Programmes::where('prog_mod_id', $prog_mod_id)->orderBy('created_at', 'desc')->get();
            // $programme = Programmes::with('programme_schedules')->where('prog_mod_id', $prog_mod_id)->orderBy('created_at', 'desc')->get();
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

    public function index($type = null)
    {   
        switch ($type) {
            case null:
                $programme = Programmes::orderBy('created_at', 'desc')->paginate($this->ADMIN_LIST_PROGRAMME_VIEW_PER_PAGE);
                break;
            case ("webinar" OR "event"):
                $programme = ProgrammeDetails::whereHas('programmes', function($query) use ($type) {
                    $query->where('prog_name', $type);
                })->paginate($this->ADMIN_LIST_PROGRAMME_VIEW_PER_PAGE);
                break;
        }

        return response()->json(['succes' => true, 'data' => $programme]);        
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
            'status'      => 'required|in:active,inactive',
            'prog_price'  => 'nullable|integer',
            'prog_file'   => 'nullable|max:3000'
        ];

        $custom_messages = [
            'prog_mod_id.exists' => 'The selected programme modules is invalid',
        ];

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

            $data['programme'] = $programme;

        } catch (Exception $e) {

            DB::rollBack();
            Log::error('Create Programme Issue : ['.$request->prog_mod_id.', '.$request->prog_name.', '.$request->prog_desc.', '.$request->status.'] '.$e->getMessage());
            return response()->json(['success' => false, 'error' => 'Failed to create a new programme. Please try again.']);
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
            return response()->json(['success' => false, 'error' => 'Failed to find programme by Id. Please try again.']);
        }

        //initialize variable
        $data = $prog_has = array();
        $programme_inserted_id = $med_file_name = $med_file_format = $med_file_path = NULL;

        //VALIDATION START
        $rules = [
            'prog_sch_id' => 'nullable',
            'prog_mod_id' => 'required|exists:programme_modules,id',
            'prog_name'   => 'required|string|max:255|unique:programmes,prog_name,'.$prog_id,
            'prog_desc'   => 'required',
            'status'      => 'required|in:active,inactive',
            'prog_price'  => 'nullable|integer',
            'prog_file'   => 'nullable|max:3000'
        ];

        $custom_messages = [
            'prog_mod_id.exists' => 'The selected programme modules is invalid',
        ];

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
        //update to programme table
        try {

            if ($request->hasFile('prog_file')) {
                $med_file_name = date('Ymd_His').'_'.str_replace(' ', '-', $request->prog_name);
                $med_file_format = $request->file('prog_file')->getClientOriginalExtension();
                $med_file_path = $request->file('prog_file')->storeAs($this->store_media_path, $med_file_name.'.'.$med_file_format);
            }

            $programme->prog_mod_id = $request->prog_mod_id;
            $programme->prog_name = $request->prog_name;
            $programme->prog_desc = $request->prog_desc;
            $programme->prog_has = json_encode($prog_has);
            $programme->prog_href = isset($request->prog_href) ? $request->prog_href : NULL;
            $programme->prog_price = $request->prog_price;
            $programme->prog_file_path = $med_file_path;
            $programme->status = $request->status;
            $programme->save();
            $data['programme'] = $programme;
            
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Update Programme Issue : ['.$request->prog_mod_id.', '.$request->prog_name.', '.$request->prog_desc.', '.$request->status.'] '.$e->getMessage());
            return response()->json(['success' => false, 'error' => 'Failed to update programme. Please try again.']);
        }

        //insert/update to schedule table
        if ($programme->programme_schedules) {
            try {
                $schedule = $programme->programme_schedules;
                $schedule->prog_sch_start_date = $request->prog_schedule['prog_sch_start_date'];
                $schedule->prog_sch_start_time = $request->prog_schedule['prog_sch_start_time'];
                $schedule->prog_sch_end_date = $request->prog_schedule['prog_sch_end_date'];
                $schedule->prog_sch_end_time = $request->prog_schedule['prog_sch_end_time'];
                $schedule->save();
                

                $data['schedule'] = $schedule;
            } catch (Exception $e) {
                DB::rollBack();
                Log::error('Update Programme Schedule Issue : ['.$prog_id.', '.json_encode($request->prog_schedule).'] '.$e->getMessage());
                return response()->json(['success' => false, 'error' => 'Failed to update programme schedule. Please try again.']);
            }
        }

        DB::commit();
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
