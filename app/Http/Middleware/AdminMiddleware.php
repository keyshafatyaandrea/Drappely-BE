<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        try {
            // Cek token
            if (!$user = JWTAuth::parseToken()->authenticate()) {
                return response()->json(['message' => 'Unauthorized'], 401);
            }

            // Cek apakah user adalah admin
            if (!$user->is_admin) {
                return response()->json(['message' => 'Forbidden - Admin access only'], 403);
            }

            $request->user = $user;
        } catch (\Exception $e) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        return $next($request);
    }
}