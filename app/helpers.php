<?php

use App\Models\Event;
use App\Models\User;

if (!function_exists('authorizeEventAccess')) {
    /**
     * Check if the event belongs to the user.
     *
     * @param Event $event
     * @param User $user
     * @return bool
     */
    function authorizeEventAccess(Event $event, User $user)
    {
        return $event->user_id === $user->id;
    }
}
