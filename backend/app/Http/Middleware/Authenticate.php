<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;

class Authenticate extends Middleware
{
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     */
protected function redirectTo($request)
{
    // Garante que não tenta redirecionar para uma rota que não existe
    if (! $request->expectsJson()) {
        return response()->json(['message' => 'Não autenticado.'], 401);
    }
}

}
