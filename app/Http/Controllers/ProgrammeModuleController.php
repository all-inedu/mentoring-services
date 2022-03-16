<?php

namespace App\Http\Controllers;

use App\Models\ProgrammeModules;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Exception;
use Illuminate\Support\Facades\Log;

class ProgrammeModuleController extends Controller
{

    public function switch($status, Request $request) 
    {
        $rules = [
            'prog_mod_id' => 'required|exists:programme_modules,id',
            'status'   => 'in:active,inactive'
        ];

        $custom_message = [
            'prog_mod_id.required' => 'Programme Module Id is required.',
            'prog_mod_id.exists' => 'Programme Module Id is invalid'
        ];

        $validator = Validator::make($request->all() + ['status' => $status], $rules, $custom_message);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'error' => $validator->errors()], 400);
        }

        DB::beginTransaction();
        try {

            if (!$programme_modules = ProgrammeModules::find($request->prog_mod_id)) {
                return response()->json(['success' => false, 'error' => 'The programme modules does not exists']);
            }
            $programme_modules->prog_mod_status = $request->status;
            $programme_modules->save();
            DB::commit();

        } catch (Exception $e) {

            DB::rollBack();
            Log::error('Switch status Issue : ['.$request->prog_mod_id.', '.$status.'] '.$e->getMessage());
            return response()->json(['success' => false, 'error' => 'Failed to switch programme module status. Please try again.']);
        }
        
        return response()->json(['success' => true, 'message' => 'The programme module has been changed to '.$status]);
    }

    public function find($prog_mod_id)
    {
        try {
            $programme_modules = ProgrammeModules::findOrFail($prog_mod_id);
        } catch (Exception $e) {
            Log::error('Find Program Modules by Id Issue : ['.$prog_mod_id.'] '.$e->getMessage());
            return response()->json(['success' => false, 'error' => 'Failed to find programme module by Id. Please try again.']);
        }
        return response()->json(['success' => true, 'data' => $programme_modules]);
    }

    public function index()
    {
        $programme_modules = ProgrammeModules::orderBy('created_at', 'desc')->get();
        return response()->json(['succes' => true, 'data' => $programme_modules]);
    }
    
    public function store(Request $request)
    {
        $rules = [
            'prog_mod_name'   => 'required|string|unique:programme_modules,prog_mod_name|max:255',
            'prog_mod_desc'   => 'required',
            'prog_mod_status' => 'required|in:active,inactive'
        ];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'error' => $validator->errors()], 400);
        }

        DB::beginTransaction();
        try {

            $programme_modules = new ProgrammeModules;
            $programme_modules->prog_mod_name = $request->prog_mod_name;
            $programme_modules->prog_mod_desc = $request->prog_mod_desc;
            $programme_modules->prog_mod_status = $request->prog_mod_status;
            $programme_modules->save();
            DB::commit();
            
        } catch (Exception $e) {

            DB::rollBack();
            Log::error('Create Programme Modules Issue : ['.$request->prog_mod_name.', '.$request->prog_mod_desc.', '.$request->prog_mod_status.'] '.$e->getMessage());
            return response()->json(['success' => false, 'error' => 'Failed to create a new programme module. Please try again.']);

        }

        return response()->json(['success' => true, 'message' => 'New programme module has been made']);
    }

    public function update($prog_mod_id, Request $request)
    {
        try {
            $programme_modules = ProgrammeModules::findOrFail($prog_mod_id);
        } catch (Exception $e) {
            Log::error('Find Program Modules by Id Issue : ['.$prog_mod_id.'] '.$e->getMessage());
            return response()->json(['success' => false, 'error' => 'Failed to find programme module by Id. Please try again.']);
        }

        $rules = [
            'prog_mod_name'   => 'required|string|unique:programme_modules,prog_mod_name,'.$prog_mod_id.'|max:255',
            'prog_mod_desc'   => 'required',
            'prog_mod_status' => 'required|in:active,inactive'
        ];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'error' => $validator->errors()], 400);
        }

        DB::beginTransaction();
        try {
            $programme_modules->prog_mod_name = $request->prog_mod_name;
            $programme_modules->prog_mod_desc = $request->prog_mod_desc;
            $programme_modules->prog_mod_status = $request->prog_mod_status;
            $programme_modules->save();
            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Update Programme Module Issue : ['.$request->prog_mod_name.', '.$request->prog_mod_desc.', '.$request->prog_mod_status.'] '.$e->getMessage());
            return response()->json(['success' => false, 'error' => 'Failed to update programme module. Please try again.']);
        }       

        return response()->json(['success' => true, 'message' => 'Programme module has been updated', 'data' => $programme_modules]);
    }

    public function delete($prog_mod_id)
    {
        //Validation
        if (!$programme_modules = ProgrammeModules::find($prog_mod_id)) {
            return response()->json(['success' => false, 'error' => 'Failed to find existing programme module.'], 400);
        } 

        DB::beginTransaction();
        try {
            $programme_modules->delete();
            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Delete Programme Module Issue : ['.$prog_mod_id.'] '.$e->getMessage());
            return response()->json(['success' => false, 'error' => 'Failed to delete the programme module. Please try again.'], 400);
        }
        return response()->json(['success' => true, 'message' => 'You\'ve successfully deleted the programme module.']);
    }
}
