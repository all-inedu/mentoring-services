<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;
use App\Models\Students;

class CheckExistingEmailAddress implements Rule
{

    protected $email;
    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
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
        $this->email = $value;
        $find = Students::where('email', $value)->count();
        return $find > 0;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'The email address : '.$this->email.' doesn\'t exists. Please use only existing email address';
    }
}
