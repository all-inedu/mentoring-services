<?php

namespace App\Http\Controllers;

use App\Models\ProgrammeDetails;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProgrammeDetailController extends Controller
{

    public function select($prog_id)
    {
        try {
            $prog_details = ProgrammeDetails::where('prog_id', $prog_id)->orderBy('created_at', 'desc')->get();
        } catch (Exception $e) {
            Log::error('Select Programme Detail Use Programme Id  Issue : ['.$prog_id.'] '.$e->getMessage());
            return response()->json(['success' => false, 'error' => 'Failed to select programme detail by programme Id. Please try again.']);
        }
        return response()->json(['success' => true, 'data' => $prog_details]);
    }

    public function find($prog_dtl_id)
    {
        try {
            $prog_details = ProgrammeDetails::with('programme_schedules', 'speakers', 'partners')->findOrFail($prog_dtl_id);
        } catch (Exception $e) {
            Log::error('Find Programme Detail by Id Issue : ['.$prog_dtl_id.'] '.$e->getMessage());
            return response()->json(['success' => false, 'error' => 'Failed to find programme detail by Id. Please try again.']);
        }
        return response()->json(['success' => true, 'data' => $prog_details]);
    }

    public function store(Request $request)
    {
        $rules = [
            'prog_id'        => 'required|exists:programmes,id',
            'dtl_category'   => 'required|in:career-bootcamp,university-application-bootcamp,competition-program,all-in-program',
            'dtl_name'       => 'required|max:255',
            'dtl_desc'       => 'required',
            'dtl_price'      => 'required|integer|min:0',
            'dtl_video_link' => 'nullable',
            'status'         => 'required|in:active,inactive'
        ];

        $custom_messages = [
            'prog_id.required'      => 'The programme id field is required',
            'prog_id.exists'        => 'The selected programme id is invalid',
            'dtl_category.required' => 'The category field is required',
            'dtl_category.in'       => 'The selected category is invalid. Should be (Career Bootcamp, University Application Bootcamp, Competition Program, ALL-in Program',
            'dtl_name.required'     => 'The event/webinar name field is required',
            'dtl_name.max'          => 'The event/webinar name must not be greater than 255 characters',
            'dtl_desc.required'     => 'The event/webinar description field is required',
            'dtl_price.required'    => 'The event/webinar price field is required',
            'dtl_price.integer'     => 'The event/webinar price must be a number',
            'dtl_price.min'         => 'The event/webinar price must be at least 0'
        ];

        $validator = Validator::make($request->all(), $rules, $custom_messages);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'error' => $validator->errors()], 400);
        }

        DB::beginTransaction();
        try {

            $prog_details = new ProgrammeDetails;
            $prog_details->prog_id = $request->prog_id;
            $prog_details->dtl_category = $request->dtl_category;
            $prog_details->dtl_name = $request->dtl_name;
            $prog_details->dtl_desc = $request->dtl_desc;
            $prog_details->dtl_price = $request->dtl_price;
            $prog_details->dtl_video_link = $request->dtl_video_link;
            $prog_details->status = $request->status;
            $prog_details->save();

            $data['prog_detail'] = $prog_details;

        } catch (Exception $e) {

            DB::rollBack();
            Log::error('Create Programme Detail Issue : ['.json_encode($request->all()).'] '.$e->getMessage());
            return response()->json(['success' => false, 'error' => 'Failed to create a new programme detail. Please try again.']);

        }

        DB::commit();
        return response()->json(['success' => true, 'message' => 'New programme detail has been made', 'data' => $data]);
    }

    public function update($prog_dtl_id, Request $request)
    {
        try {
            $prog_details = ProgrammeDetails::findOrFail($prog_dtl_id);
        } catch (Exception $e) {
            Log::error('Find Program Detail by Id Issue : ['.$prog_dtl_id.'] '.$e->getMessage());
            return response()->json(['success' => false, 'error' => 'Failed to find programme detail by Id. Please try again.']);
        }

        $rules = [
            'prog_id'        => 'required|exists:programmes,id',
            'dtl_category'   => 'required|in:career-bootcamp,university-application-bootcamp,competition-program,all-in-program',
            'dtl_name'       => 'required|max:255',
            'dtl_desc'       => 'required',
            'dtl_price'      => 'required|integer|min:0',
            'dtl_video_link' => 'nullable',
            'status'         => 'required|in:active,inactive'
        ];

        $custom_messages = [
            'prog_id.required'      => 'The programme id field is required',
            'prog_id.exists'        => 'The selected programme id is invalid',
            'dtl_category.required' => 'The category field is required',
            'dtl_category.in'       => 'The selected category is invalid. Should be (Career Bootcamp, University Application Bootcamp, Competition Program, ALL-in Program',
            'dtl_name.required'     => 'The event/webinar name field is required',
            'dtl_name.max'          => 'The event/webinar name must not be greater than 255 characters',
            'dtl_desc.required'     => 'The event/webinar description field is required',
            'dtl_price.required'    => 'The event/webinar price field is required',
            'dtl_price.integer'     => 'The event/webinar price must be a number',
            'dtl_price.min'         => 'The event/webinar price must be at least 0'
        ];

        $validator = Validator::make($request->all(), $rules, $custom_messages);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'error' => $validator->errors()], 400);
        }

        DB::beginTransaction();
        try {

            $prog_details->prog_id = $request->prog_id;
            $prog_details->dtl_category = $request->dtl_category;
            $prog_details->dtl_name = $request->dtl_name;
            $prog_details->dtl_desc = $request->dtl_desc;
            $prog_details->dtl_price = $request->dtl_price;
            $prog_details->dtl_video_link = $request->dtl_video_link;
            $prog_details->status = $request->status;
            $prog_details->save();

            $data['prog_detail'] = $prog_details;

        } catch (Exception $e) {

            DB::rollBack();
            Log::error('Create Programme Detail Issue : ['.json_encode($request->all()).'] '.$e->getMessage());
            return response()->json(['success' => false, 'error' => 'Failed to create a new programme detail. Please try again.']);

        }

        DB::commit();
        return response()->json(['success' => true, 'message' => 'Programme detail has been updated', 'data' => $data]);
    }

    public function delete($prog_dtl_id)
    {
        //Validation
        if (!$prog_details = ProgrammeDetails::find($prog_dtl_id)) {
            return response()->json(['success' => false, 'error' => 'Failed to find existing programme detail'], 400);
        } 

        DB::beginTransaction();
        try {
            $prog_details->delete();
            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Delete Programme Detail Issue : ['.$prog_dtl_id.'] '.$e->getMessage());
            return response()->json(['success' => false, 'error' => 'Failed to delete the programme detail. Please try again.'], 400);
        }
        return response()->json(['success' => true, 'message' => 'You\'ve successfully deleted programme detail']);
    }
}
