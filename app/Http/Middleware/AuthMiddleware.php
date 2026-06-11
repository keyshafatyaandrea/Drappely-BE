<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;
use Symfony\Component\HttpFoundation\Response;

class AuthMiddleware
{
public function handle(Request $request, Closure $next): Response
{
    try {
        if (!$user = JWTAuth::parseToken()->authenticate()) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }
    } catch (\Exception $e) {
        return response()->json(['message' => 'Token invalid or expired'], 401);
    }

    $request->merge(['user' => $user]); 
    return $next($request);
}
}