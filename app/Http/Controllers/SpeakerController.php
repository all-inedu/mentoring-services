<?php

namespace App\Http\Controllers;

use App\Models\ProgrammeDetails;
use App\Models\Speakers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class SpeakerController extends Controller
{
    
    public function store(Request $request)
    {
        $rules = [
            'prog_dtl_id'   => [
                            'required',
                            Rule::exists(ProgrammeDetails::class, 'id')->where(function($query) {
                                $query->where('dtl_category', '!=', NULL);
                            }),
                        ],
            'sp_name'       => 'required|alpha|max:255',
            'sp_title'      => 'required|alpha|max:255',
            'sp_short_desc' => 'required',
            'status'        => 'required|in:active,inactive'
        ];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'error' => $validator->errors()], 400);
        }

        //insert to schedule table
        try {
            $speaker = new Speakers;
            $speaker->prog_dtl_id = $request->prog_dtl_id;
            $speaker->sp_name = $request->sp_name;
            $speaker->sp_title = $request->sp_title;
            $speaker->sp_short_desc = $request->sp_short_desc;
            $speaker->status = $request->status;
            $speaker->save();

            $data['speaker'] = $speaker;

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Save Speaker Issue : ['.$request->prog_dtl_id.', '.json_encode($speaker).'] '.$e->getMessage());
            return response()->json(['success' => false, 'error' => 'Failed to save speaker. Please try again.']);
        }

        return response()->json(['success' => true, 'message' => 'Speaker has been added', 'data' => $data]);
    }

    public function update($sp_id, Request $request)
    {
        try {
            $speaker = Speakers::findOrFail($sp_id);
        } catch (Exception $e) {
            Log::error('Find Speaker by Id Issue : ['.$sp_id.'] '.$e->getMessage());
            return response()->json(['success' => false, 'error' => 'Failed to find speaker by Id. Please try again.']);
        }

        $rules = [
            'prog_dtl_id'   => [
                            'required',
                            Rule::exists(ProgrammeDetails::class, 'id')->where(function($query) {
                                $query->where('dtl_category', '!=', NULL);
                            }),
                        ],
            'sp_name'       => 'required|alpha|max:255',
            'sp_title'      => 'required|alpha|max:255',
            'sp_short_desc' => 'required',
            'status'        => 'required|in:active,inactive'
        ];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'error' => $validator->errors()], 400);
        }

        //insert to schedule table
        try {
            $speaker->prog_dtl_id = $request->prog_dtl_id;
            $speaker->sp_name = $request->sp_name;
            $speaker->sp_title = $request->sp_title;
            $speaker->sp_short_desc = $request->sp_short_desc;
            $speaker->status = $request->status;
            $speaker->save();

            $data['speaker'] = $speaker;

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Update Speaker Issue : ['.$request->prog_dtl_id.', '.json_encode($speaker).'] '.$e->getMessage());
            return response()->json(['success' => false, 'error' => 'Failed to update speaker. Please try again.']);
        }

        return response()->json(['success' => true, 'message' => 'Speaker has been updated', 'data' => $data]);
    }

    public function delete($sp_id)
    {
        //Validation
        if (!$speaker = Speakers::find($sp_id)) {
            return response()->json(['success' => false, 'error' => 'Failed to find existing speaker'], 400);
        } 

        DB::beginTransaction();
        try {
            $speaker->delete();
            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Delete Speaker Issue : ['.$sp_id.'] '.$e->getMessage());
            return response()->json(['success' => false, 'error' => 'Failed to delete speaker. Please try again.'], 400);
        }
        return response()->json(['success' => true, 'message' => 'You\'ve successfully deleted speaker']);
    }
}
