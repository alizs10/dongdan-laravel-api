<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ExpenseBelongsToEvent implements ValidationRule
{
    protected $event;

    public function __construct($event)
    {
        $this->event = $event;
    }

    /**
     * Run the validation rule.
     *
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!$this->event->expenses()->where('expenses.id', $value)->exists()) {
            $fail('The selected expense does not belong to this event.');
        }
    }
}
