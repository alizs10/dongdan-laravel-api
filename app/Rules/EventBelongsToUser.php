<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class EventBelongsToUser implements ValidationRule
{
    protected $user;

    public function __construct($user)
    {
        $this->user = $user;
    }

    /**
     * Run the validation rule.
     *
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!$this->user->events()->withTrashed()->where('events.id', $value)->exists()) {
            $fail('The selected contact does not belong to this user.');
        }
    }
}
