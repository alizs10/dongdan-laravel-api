<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\Event;
use Illuminate\Support\Facades\Auth;

class CheckEventOwnership
{
    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $eventId = $request->route('event_id'); // Assuming the route parameter is 'event'
        $event = Event::findOrFail($eventId);

        if (!authorizeEventAccess($event, Auth::user())) {
            // Abort with a 403 Forbidden response if unauthorized
            abort(403, 'Unauthorized access to this event.');
        }

        return $next($request);
    }
}
