<?php

namespace App\Rules;

use App\Models\UserSchedule;
use Illuminate\Contracts\Validation\Rule;

class CheckAvailabilityUserSchedule implements Rule
{
    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct($mentor_id)
    {
        $this->mentor_id = $mentor_id;
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
        if ($value < date('Y-m-d H:i:s')) {
            return false;
        }

        $day = date('l', strtotime($value));
        $time = date('H:i', strtotime($value));

        $find = UserSchedule::where('us_start_time', $time)->where('us_days', $day)->get();
        return count($find) > 0;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'The selected date is invalid';
    }
}
