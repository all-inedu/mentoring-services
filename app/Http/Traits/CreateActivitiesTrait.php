<?php

namespace App\Http\Traits;

use Illuminate\Http\Request;
use App\Rules\RolesChecking;
use Illuminate\Support\Facades\Validator;
use App\Models\Programmes;
use App\Models\Students;
use App\Models\StudentActivities;
use App\Http\Controllers\TransactionController;
use App\Models\WatchDetail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

trait CreateActivitiesTrait
{
    public function store_activities ($request)
    {
        //select programmes 
        $programmes = Programmes::find($request['prog_id']);
        $prog_name = $programmes->prog_name;
        $prog_price = $programmes->prog_price; //price that will be inserted into transaction

        DB::beginTransaction();
        try {

            //! bikin prog pricenya get dari programme detail kalau programme details id nya tidak null

            // check if the student is the internal student or external
            $student = Students::find($request['student_id']);
            $total_amount = ($student->imported_id != NULL) ? 0 : $prog_price; //set to 0 if student is internal student

            $activities = StudentActivities::create($request);

            // if programme is webinar
            // then input detail video to detail watch
            // to save student watching progress 
            if ($prog_name == "webinar") {
                //? lanjut abis bikin list webinar
                // $watch_detail = new WatchDetail;
                // $watch_detail->duration
            }
            
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
}