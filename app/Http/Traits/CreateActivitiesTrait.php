<?php

namespace App\Http\Traits;

use App\Http\Controllers\HelperController;
use Illuminate\Http\Request;
use App\Rules\RolesChecking;
use Illuminate\Support\Facades\Validator;
use App\Models\Programmes;
use App\Models\Students;
use App\Models\StudentActivities;
use App\Http\Controllers\TransactionController;
use App\Models\ProgrammeDetails;
use App\Models\WatchDetail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

trait CreateActivitiesTrait
{
    public function store_activities ($request, $video_duration)
    {
        $helper = new HelperController;
        // return $this->get_video_duration("https://youtu.be/mHA4BxZTXlk");
        //select programmes 
        $programmes = Programmes::find($request['prog_id']);
        $prog_name = $programmes->prog_name;
        $prog_price = $programmes->prog_price; //price that will be inserted into transaction

        DB::beginTransaction();
        try {

            //! bikin prog pricenya get dari programme detail kalau programme details id nya tidak null

            $activities = StudentActivities::create($request);

            // if programme is webinar
            // then input detail video to detail watch
            // to save student watching progress 
            if ($prog_name == "webinar") {
                // get programme detail to get the programme dtl name and programme dtl price from programme details
                $prog_detail = ProgrammeDetails::find($request['prog_dtl_id']);
                $prog_name = $prog_detail->dtl_name;
                $prog_price = $prog_detail->dtl_price;
                $prog_video_link = $prog_detail->dtl_video_link;
                
                $watch_detail = $activities->watch_detail()->create([
                    // 'video_duration' => $helper->videoDetails($prog_video_link),
                    'video_duration' => $video_duration
                ]);
                
            }

            // check if the student is the internal student or external
            $student = Students::find($request['student_id']);
            $total_amount = ($student->imported_id != NULL) ? 0 : $prog_price; //set to 0 if student is internal student
            
            $response['activities'] = $activities;
            $st_act_id = $activities->id;

            $data = [
                'student_id' => $request['student_id'],
                'st_act_id'   => $st_act_id,
                'amount'       => $prog_price,
                'total_amount' => $total_amount,
                'status'       => 'paid' //! sementara langsung paid, ke depannya akan diubah dari pending dlu
            ];

            $transaction = new TransactionController;
            $response['transaction'] = $transaction->store($data);

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Create Student Activities '.ucfirst($prog_name).' Issue : ['.json_encode($request).'] '.$e->getMessage());
            return response()->json(['success' => false, 'error' => 'Failed to create student activities. Please try again.']);
        }

        return response()->json(['success' => true, 'message' => 'Activities has been created', 'data' => $response]);
    }

    public function get_video_duration($url)
    {
        parse_str(parse_url($url,PHP_URL_QUERY),$arr);
        $video_id=$arr['v']; 


        $data=@file_get_contents('http://gdata.youtube.com/feeds/api/videos/'.$video_id.'?v=2&alt=jsonc');
        if (false===$data) return false;

        $obj=json_decode($data);

        return $obj->data->duration;

    }
}