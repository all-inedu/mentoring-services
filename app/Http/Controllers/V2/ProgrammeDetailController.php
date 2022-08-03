<?php

namespace App\Http\Controllers\V2;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ProgrammeDetails;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class ProgrammeDetailController extends Controller
{
    public function update($prog_dtl_id, Request $request)
    {
        if (!$prog_details = ProgrammeDetails::find($prog_dtl_id)) {
            return response()->json(['success' => false, 'error' => 'Failed to find programme detail by Id. Please try again.']);
        }

        $rules = [
            'prog_id'        => 'required|exists:programmes,id',
            // 'dtl_category'   => 'required|in:career-bootcamp,university-application-bootcamp,competition-program,all-in-program,university-preparation-webinar,career-industry-webinar',
            'dtl_category'   => 'required',
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
            $prog_details->dtl_date = null;
            $prog_details->save();
            

        } catch (Exception $e) {

            DB::rollBack();
            Log::error('Update Webinar Issue : ['.json_encode($request->all()).'] '.$e->getMessage());
            return response()->json(['success' => false, 'error' => 'Failed to update the webinar. Please try again.']);

        }
        DB::commit();
        return response()->json(['success' => true, 'message' => 'Programme detail has been updated', 'data' => $prog_details]);
    }
}
