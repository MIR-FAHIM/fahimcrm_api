<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
class CheckAppToken
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next)
    {
        // Check if the 'token' header exists
        $token = $request->header('token');
 
        if (!$token) {
            return response()->json([
                'status' => 450,
                'message' => 'App token not provided',
            ], 450);
        }
        $user = User::where('app_token', $token)->first();
        // Check if the token matches the user's app_token
            if (!$user) {
                return response()->json([
                    'status' => 450,
                    'message' => 'Invalid app token',
                ], 450);
            }
        

        // Proceed with the request if the token is valid
        return $next($request);
    }
}
