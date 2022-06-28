<?php

namespace App\Rules;

use App\Models\ProgrammeDetails;
use Illuminate\Contracts\Validation\Rule;

class CategoryProgrammeChecker implements Rule
{
    /**
     * Create a new rule instance.
     *
     * @return void
     */

    protected $programme;

    public function __construct($programme)
    {
        $this->programme = $programme;
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
        return ProgrammeDetails::whereHas('programmes', function($query) {
            $query->where('prog_name', $this->programme);
        })->where('dtl_category', $value)->count();
        
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'Invalid Category';
    }
}
