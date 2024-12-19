<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckAdminRole
{
    public function handle(Request $request, Closure $next)
    {
        if (!$request->user('sanctum')) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $user = $request->user('sanctum');

        if (!($user instanceof \App\Models\User) || $user->role !== 'admin') {
            return response()->json([
                'error' => 'Forbidden',
                'message' => 'This resource requires admin privileges'
            ], 403);
        }

        return $next($request);
    }
}