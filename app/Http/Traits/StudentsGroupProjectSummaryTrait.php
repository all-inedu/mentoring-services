<?php

namespace App\Http\Traits;

use App\Models\GroupProject;

trait StudentsGroupProjectSummaryTrait
{
    public function group_project_summary($student_id)
    {
        // group project
        // new request
        $data['request'] = GroupProject::whereHas('group_participant', function ($query) use ($student_id) {
            $query->where('student_id', $student_id)->where('participants.status', 0);
        })->count();

        // in progress
        $data['upcoming'] = GroupProject::whereHas('group_participant', function ($query) use ($student_id) {
            $query->where('student_id', $student_id)->where('participants.status', 1);
        })->where('status', 'in progress')->count();

        // history
        $data['history'] = GroupProject::whereHas('group_participant', function ($query) use ($student_id) {
            $query->where('student_id', $student_id)->where('participants.status', 1);
        })->where('status', 'completed')->count();

        return $data;
    }
}