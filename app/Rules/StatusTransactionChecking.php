<?php

namespace App\Rules;

use App\Models\Transaction;
use Illuminate\Contracts\Validation\Rule;

class StatusTransactionChecking implements Rule
{
    /**
     * Create a new rule instance.
     *
     * @return void
     */

    public $status;

    public function __construct($status)
    {
        $this->status = $status;
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
        if ($this->status == "paid") {
            $transaction = Transaction::where(function($query) {
                $query->where('payment_proof', '!=', NULL)
                ->where('payment_method', '!=', NULL)
                ->where('payment_date', '!=', NULL);
            })->get();
        }
        return count($transaction) > 0;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'The validation error message.';
    }
}
