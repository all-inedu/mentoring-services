<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class EmailHandlerController extends Controller
{
    public function mentee_confirm_meeting(Request $request)
    {
        $person = $request->person;
        $meeting_id = $request->key;
        $activities_controller = new StudentActivitiesController;
        switch ($request->action) {
            case "accept":
                $response = $activities_controller->confirmation_personal_meeting($person, $meeting_id, $request);
                break;

            case "reject":
                $response = $activities_controller->cancel_reject_personal_meeting($person, 'reject', $meeting_id, $request);
                break;
        }
        
        $response = $response->getData();
        if ($response->success === false) {
            echo $response->error;
        } else {
            echo $response->message;
        }
    }
}
