<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class IsSuperAdmin
{
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();

        if (!$user || !$user->hasRole('SA')) {
            return response()->json(['error' => 'Acesso não autorizado.'], 403);
        }

        return $next($request);
    }
}
