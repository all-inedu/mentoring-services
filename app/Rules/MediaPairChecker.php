<?php

namespace App\Rules;

use App\Models\Medias;
use Illuminate\Contracts\Validation\Rule;

class MediaPairChecker implements Rule
{
    protected $student_id;
    protected $university_id;
    protected $university_name;
    private $custom_message;
    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct($student_id, $university_id, $university_name)
    {
        $this->student_id = $student_id;
        $this->university_id = $university_id;
        $this->university_name = $university_name;
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
        if (!$media = Medias::where('id', $value)->where('student_id', $this->student_id)->first()) {
            $this->custom_message = 'Looks like you don\'t have the media with id '.$value;
            return false;
        }

        if ($media->uni_shortlisted()->where('uni_shortlisted_id', $this->university_id)->first()) {
            $this->custom_message = 'The file has already been submitted to '. $this->university_name;
            return false;
        }

        return true;

    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return $this->custom_message;
        // return trans('media_id.invalid', ['message' => $this->message]);
    }
}
