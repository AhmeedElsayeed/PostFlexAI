<?php

namespace App\Http\Middleware;

use App\Services\SecurityService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class IpAccess
{
    protected $securityService;

    public function __construct(SecurityService $securityService)
    {
        $this->securityService = $securityService;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $ip = $request->ip();

        // Check if IP is whitelisted
        if ($this->securityService->isIpWhitelisted($ip)) {
            return $next($request);
        }

        // Log the blocked access attempt
        $this->securityService->logEvent(
            'ip_access',
            'blocked',
            null,
            $request,
            ['ip_address' => $ip],
            'تم حظر الوصول من عنوان IP غير مصرح به'
        );

        return response()->json([
            'success' => false,
            'message' => 'تم حظر الوصول من عنوان IP غير مصرح به'
        ], Response::HTTP_FORBIDDEN);
    }
} 