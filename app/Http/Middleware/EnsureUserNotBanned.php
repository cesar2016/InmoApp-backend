<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserNotBanned
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->banned_at !== null) {
            return response()->json([
                'message' => 'Su periodo de prueba GRATUITO ha caducado, póngase en contacto con el Server Administrator',
                'code' => 'payment_expired',
            ], 403);
        }

        return $next($request);
    }
}
