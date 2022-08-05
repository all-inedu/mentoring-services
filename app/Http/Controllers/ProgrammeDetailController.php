<?php

namespace App\Http\Controllers;

use App\Models\ProgrammeDetails;
use App\Models\StudentActivities;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Students;

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
            $prog_details = ProgrammeDetails::with('programme_schedules', 'speakers', 'partners', 'student_activities', 'student_activities.students', 'student_activities.watch_detail')->withCount('student_activities')->findOrFail($prog_dtl_id);
            $data = array(
                'prog_id' => $prog_details->prog_id,
                'dtl_category' => ucwords(str_replace('-', ' ', $prog_details->dtl_category)),
                'dtl_name' => $prog_details->dtl_name,
                'dtl_desc' => $prog_details->dtl_desc,
                'dtl_price' => $prog_details->dtl_price,
                'dtl_video_link' => $prog_details->dtl_video_link,
                'status' => $prog_details->status,
                'viewers' => $prog_details->student_activities_count
            );

            foreach ($prog_details->programme_schedules as $schedule) {
                $data['schedules'][] = array(
                    'id' => $schedule->id,
                    'prog_dtl_id' => $schedule->prog_dtl_id,
                    'start_date' => $schedule->prog_sch_start_date,
                    'start_time' => $schedule->prog_sch_start_time,
                    'end_date' => $schedule->prog_sch_end_date,
                    'end_time' => $schedule->prog_sch_end_time
                );
            }

            foreach ($prog_details->student_activities as $st_data) {
                $data['student_list'][] = array(
                    'full_name' => $st_data->students->first_name.' '.$st_data->students->last_name,
                    'watch_at' => date('d F Y', strtotime($st_data->created_at))
                );
            }

            foreach ($prog_details->speakers as $speaker) {
                $data['speakers'][] = array(
                    'sp_name' => $speaker->sp_name,
                    'sp_title' => $speaker->sp_title,
                    'sp_desc' => $speaker->sp_short_desc
                );
            }

            foreach ($prog_details->partners as $partner) {
                $data['partners'][] = array(
                    'pt_name' => $partner->pt_name,
                    'pt_image' => $partner->pt_image,
                    'pt_website' => $partner->pt_website
                );
            }

            $viewer = StudentActivities::where('prog_dtl_id', $prog_dtl_id)->count();
            // $prog_details['viewer'] = $viewer;
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
            'dtl_category'   => 'required|in:career-bootcamp,university-application-bootcamp,competition-program,all-in-program,university-preparation-webinar,career-industry-webinar',
            'dtl_name'       => 'required|max:255',
            'dtl_desc'       => 'required',
            'dtl_price'      => 'required|integer|min:0',
            'dtl_video_link' => 'nullable|required_if:prog_id,3', //prog_id 3 is webinar
            'dtl_date'       => 'nullable|date|after:today|date_format:Y-m-d H:i',
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
            $prog_details->dtl_date = $request->dtl_date;
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
            'dtl_category'   => 'required|in:career-bootcamp,university-application-bootcamp,competition-program,all-in-program,university-preparation-webinar,career-industry-webinar',
            'dtl_name'       => 'required|max:255',
            'dtl_desc'       => 'required',
            'dtl_price'      => 'required|integer|min:0',
            'dtl_video_link' => 'nullable|required_if:prog_id,3', //prog_id 3 is webinar
            'dtl_date'       => 'nullable|date|after:today|date_format:Y-m-d H:i',
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

    public function viewer($webinar_id)
    {
        $viewer = Students::select(['id', 'first_name', 'last_name'])->withAndWhereHas('student_activities', function($query) use ($webinar_id) {
            return $query->where('prog_id', 3)->where('prog_dtl_id', $webinar_id)->select(['student_id', 'id', 'created_at']);
        })->get();

        foreach ($viewer as $activities) {
            $activities['watch_date'] = $activities->student_activities[0]->created_at;
        }

        $viewer->transform(function ($item) {
            return $item->only(['id', 'first_name', 'last_name', 'watch_date']);
        });

        return response()->json(['success' => true, 'data' => $viewer]);
    }
}
