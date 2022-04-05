<?php

namespace App\Rules;

use App\Models\Students;
use Illuminate\Contracts\Validation\Rule;
use App\Models\User;

class PersonChecking implements Rule
{
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
        if ($this->person == "user") {
            $find = User::where('id', $value)->get();
        } else if ($this->person == "student") {
            $find = Students::where('id', $value)->get();
        } else {
            $find = array();
        }

        return count($find) > 0;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'The '.$this->person.' id is not exist or invalid';
    }
}
