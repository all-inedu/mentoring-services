<?php

namespace App\Rules;

use App\Models\StudentActivities;
use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Facades\Auth;

class PersonalMeetingChecker implements Rule
{

    protected $person;
    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct($person)
    {
        $this->person = $person;
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        // get id depends on who's the user role
        $id = $this->person == 'student' ? Auth::guard('student-api')->user()->id : Auth::guard('api')->user()->id;
        
        $activities = StudentActivities::where('id', $value)
                ->when($this->person == "student", function ($q) use ($id) {
                    $q->where('student_id', $id);
                })->when($this->person == "mentor", function ($q) use ($id) {
                    $q->where('user_id', $id);
                })->first();

        return $activities;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'The activities Id is invalid';
    }
}
