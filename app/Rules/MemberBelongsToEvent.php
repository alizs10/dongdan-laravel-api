<?php

namespace App\Rules;

use App\Models\Event;
use App\Models\EventMember;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class MemberBelongsToEvent implements ValidationRule
{

    protected $eventId;

    public function __construct($eventId)
    {
        $this->eventId = $eventId;
    }


    /**
     * Run the validation rule.
     *
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $event = Event::find($this->eventId);

        if (!$event || !$event->members()->where('event_members.id', $value)->exists()) {
            $fail('The selected member does not belong to this event.');
        }
    }
}
