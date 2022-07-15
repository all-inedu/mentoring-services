<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\MeetingMinutes;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Exception;
use Illuminate\Support\Facades\Log;

class MeetingMinuteController extends Controller
{

    public function select($st_act_id)
    {
        if (!$meeting_minute = MeetingMinutes::where('st_act_id', $st_act_id)->first()) {
            return response()->json(['success' => false, 'error' => 'Couldn\'t find the Meeting minute']);
        }

        return response()->json(['success' => true, 'data' => $meeting_minute]);
    }
    
    public function store(Request $request)
    {
        if (!$meeting_minute = MeetingMinutes::where('st_act_id', $request->st_act_id)->first()) {
            $rules['st_act_id'] = 'required|exists:student_activities,id|unique:meeting_minutes,st_act_id';
            $meeting_minute = new MeetingMinutes;
            $meeting_minute->st_act_id = $request->st_act_id;
        }

        $rules = [
            'academic_performance' => 'nullable',
            'exploration' => 'nullable',
            'writing_skills' => 'nullable',
            'personal_brand' => 'nullable',
            'mt_todos_note' => 'required',
            'st_todos_note' => 'required',
        ];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'error' => $validator->errors()], 400);
        }

        DB::beginTransaction();
        try {

            $meeting_minute->academic_performance = $request->academic_performance;
            $meeting_minute->exploration = $request->exploration;
            $meeting_minute->writing_skills = $request->writing_skills;
            $meeting_minute->personal_brand = $request->personal_brand;
            $meeting_minute->mt_todos_note = $request->mt_todos_note;
            $meeting_minute->st_todos_note = $request->st_todos_note;
            $meeting_minute->save();

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Create Meeting Minute Issue : '.$e->getMessage());
            return response()->json(['success' => false, 'error' => 'Failed to create meeting minute. Please try again.']);
        }

        return response()->json(['success' => true, 'message' => 'The meeting minute has been saved.', 'data' => $meeting_minute]);
    }
}
