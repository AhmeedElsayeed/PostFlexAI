<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class SecureSession
{
    public function handle(Request $request, Closure $next)
    {
        // Set secure session parameters
        config([
            'session.secure' => true,
            'session.http_only' => true,
            'session.same_site' => 'lax'
        ]);

        // Regenerate session ID periodically
        if (!$request->session()->has('last_activity')) {
            $request->session()->regenerate();
            $request->session()->put('last_activity', time());
        }

        // Check for session timeout (30 minutes)
        if (time() - $request->session()->get('last_activity') > 1800) {
            $request->session()->flush();
            return response()->json([
                'message' => 'Session expired. Please login again.'
            ], 401);
        }

        $request->session()->put('last_activity', time());

        return $next($request);
    }
} 