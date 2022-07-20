<?php

namespace App\Rules;

use App\Models\GroupProject;
use Illuminate\Contracts\Validation\Rule;

class IsJoinGroupChecker implements Rule
{
    /**
     * Create a new rule instance.
     *
     * @return void
     */
    protected $user_id;

    public function __construct($user_id)
    {
        $this->user_id = $user_id;
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
        $find = GroupProject::where('group_projects.id', $value)->where(function($query) {
            $query->where('user_id', $this->user_id)->orWhereHas('assigned_mentor', function ($query1) {
                $query1->where('users.id', $this->user_id);
            });
        })->count();

        return $find > 0 ? true : false;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'The group id is invalid or you\'re not belong to this group';
    }
}
