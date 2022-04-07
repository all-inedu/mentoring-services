<?php

namespace App\Rules;

use App\Models\MediaCategory;
use Illuminate\Contracts\Validation\Rule;

class MediaTermsChecker implements Rule
{
    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct($category_id)
    {
        $this->category_id = $category_id;
        $this->message = null;
        $this->error = 0;
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
        $med_cat = MediaCategory::find($this->category_id);
        $med_cat_term = $med_cat->terms;

        switch (strtolower($med_cat_term)) {
            case "required":
                if (!$value || empty($value) || $value == null || $value == "") {
                    $this->message = 'Status must be verified or not-verified because the media category you choose needed a file to be verified';
                    $this->error++;
                }
                break;
            
            case "not-required":
                if ($value) {
                    $this->message = 'Status must be null because the media of media category you choose does not need to be verified';
                    $this->error++;
                }
                break;
        }

        return $this->error > 0 ? 0 : 1;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        // return "The :attribute is invalid";
        return $this->message;
    }
}
