<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckRole
{
    public function handle(Request $request, Closure $next, ...$roles)
    {
        if (!$request->user('sanctum')) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $user = $request->user('sanctum');

        // Cek instance user
        if ($user instanceof \App\Models\User) {
            // Untuk Admin dan Superadmin
            if (!in_array($user->role, $roles)) {
                return response()->json([
                    'error' => 'Forbidden',
                    'message' => 'You do not have permission to access this resource'
                ], 403);
            }
        } elseif ($user instanceof \App\Models\Member) {
            // Untuk Member
            if (!in_array('member', $roles)) {
                return response()->json([
                    'error' => 'Forbidden',
                    'message' => 'Members cannot access this resource'
                ], 403);
            }

            // Cek status membership
            if (!$user->isActive()) {
                return response()->json([
                    'error' => 'Forbidden',
                    'message' => 'Your membership is not active'
                ], 403);
            }
        }

        return $next($request);
    }
}