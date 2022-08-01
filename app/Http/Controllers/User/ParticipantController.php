<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\GroupProject;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use App\Models\Students;
use App\Models\Participant;

class ParticipantController extends Controller
{

    public function store(Request $request)
    {
        $rules = [
            'group_id' => 'required|exists:group_projects,id',
            'participant.*' => 'required|exists:students,id',
        ];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'error' => $validator->errors()], 400);
        }

        $group = GroupProject::find($request->group_id);
        //* count jumlah participant untuk di looping
        $added_participant = count($request->participant); 

        DB::beginTransaction();
        try {
            $row_success = 0;
            $error_exists = ''; //* variable that hold the value of email when student email cannot be found
            $error_joined = ''; //* variable that hold the value of student name when student has already join the group
            for ($i = 0; $i < $added_participant ; $i++) {
                //* get student id by inputed email on participant variable
                if (!$student = Students::find($request->participant[$i])) {
                    $error_exists .= ($i > 0 ? ", " : "") . $request->participant[$i];
                    continue;
                }

                //* check if student has already joined into another group project
                if ($detail = $group->group_participant->where('id', $student->id)->first()) {
                    if (($detail->pivot->status == 0) || ($detail->pivot->status == 1)) {
                        //* input the student that already joined into another group projects to array failed participant. Needed for show on error message
                        $error_joined .= ($i > 0 ? ", " : "") . $detail->first_name.' '.$detail->last_name;
                        continue;
                    }
                    
                    // when pivot status = 2
                    // delete the participant
                    $group->group_participant()->detach($student->id);
                }

                $participant = new Participant;
                $participant->group_id = $request->group_id;
                $participant->student_id = $student->id;
                $participant->save();
                $row_success++;
            }

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Add Participant Group Project Issue : ['.json_encode($request->all()).'] '.$e->getMessage());
            return response()->json(['success' => false, 'error' => "Something went wrong. Please try again or contact our administrator"]);
        }

        // create variable response that hold various message of error
        if ($error_exists != '') {
            $response['error']['exists'] = "We couldn't find the following Id : ". $error_exists;
        }

        if ($error_joined != '') {
            $response['error']['joined'] = "These students [" . $error_joined . "] has already joined the group";
        }

        // when error exist return error
        if (!empty($response['error'])) {
            return response()->json(['success' => false, 'error' => $response['error']]);
        }

        $attendee = $row_success != 0 ? $row_success : 0;

        return response()->json([
            'success' => true, 
            'message' => $attendee.' participant has been added to the Group Project : '.$group->project_name
                        // $failed_participant.' has already joined the Group Project',
        ]);
    }
}
