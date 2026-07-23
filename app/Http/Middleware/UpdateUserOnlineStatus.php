<?php

namespace App\Http\Middleware;

use App\Events\UserStatusChanged;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;

class UpdateUserOnlineStatus
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {

        if (Auth::check()) {
            $user = Auth::user();
            $wasOffline = $user->isOnline();

            $user->setOnline();

            if ($wasOffline) {
                broadcast(new UserStatusChanged($user));
            }
        }

        return $next($request);
    }
}
