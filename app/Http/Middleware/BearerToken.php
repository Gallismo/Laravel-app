<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use Illuminate\Support\Str;


class BearerToken
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {

        $token = Str::replaceFirst('Bearer ', '', $request->header('authorization'));

        foreach (User::all() as $user) {
            if(Hash::check($token, $user->api_token)) {
                auth()->login($user);
            }
        }

        if (!auth()->check()) {
            return response()->json(['error' => ['code' => '401', 'message' => 'Unauthorized']], 401);
        }

        return $next($request);
    }
}
