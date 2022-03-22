<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;
use App\Models\User;

class RolesChecking implements Rule
{
    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public $role;

    public function __construct($role)
    {
        $this->role = $role;
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
        $user = User::whereHas('roles', function($query) {
            $query->where('role_name', $this->role);
        })->where('id', $value)->get();
        return count($user) > 0;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'User Id is invalid';
    }
}
