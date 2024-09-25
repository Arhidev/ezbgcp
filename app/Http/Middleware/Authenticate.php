<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Auth\Middleware\Authenticate as Middleware;

//AuthSec
use App\Traits\AuthSec;

//Log
use Illuminate\Support\Facades\Log;
class Authenticate extends Middleware
{
    use AuthSec;
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return string|null
     */
    protected function redirectTo($request)
    {
        if (! $request->expectsJson()) {
            return url(env('SPA_URL') . '/login');
        }
    }

    //handle
    public function handle($request, Closure $next, ...$guards)
    {
        $request = $this->get_bearer($request);
        if($request == null)
        {
            return response()->json(['error' => ['Unauthorized']], 401);
        }
        //get the auth user
        $user = auth('sanctum')->user();
        //check if he is suspended
        if (($user != null) && (($user->role == 1 && $user->status_id != 1) ||($user->role == 2 && $user->status_id == 3)))
        {
            return response()->json(['error' => ['Unauthorized']], 401);
        }

        $this->authenticate($request, $guards);
        return $next($request);
    }
}
