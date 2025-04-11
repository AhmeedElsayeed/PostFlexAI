<?php

namespace App\Http\Controllers;

use App\Models\IpWhitelist;
use App\Models\SecurityLog;
use App\Services\SecurityService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class SecurityController extends Controller
{
    protected $securityService;

    public function __construct(SecurityService $securityService)
    {
        $this->securityService = $securityService;
        $this->middleware(['auth', 'admin']);
    }

    /**
     * Get security statistics.
     */
    public function stats()
    {
        return response()->json([
            'success' => true,
            'data' => $this->securityService->getSecurityStats()
        ]);
    }

    /**
     * Get security logs with filters.
     */
    public function logs(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'event_type' => 'nullable|string',
            'status' => 'nullable|string',
            'user_id' => 'nullable|integer',
            'ip_address' => 'nullable|string',
            'device_type' => 'nullable|string',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $query = SecurityLog::query();

        if ($request->has('event_type')) {
            $query->where('event_type', $request->event_type);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->has('ip_address')) {
            $query->where('ip_address', $request->ip_address);
        }

        if ($request->has('device_type')) {
            $query->where('device_type', $request->device_type);
        }

        if ($request->has('start_date')) {
            $query->where('created_at', '>=', $request->start_date);
        }

        if ($request->has('end_date')) {
            $query->where('created_at', '<=', $request->end_date);
        }

        $perPage = $request->input('per_page', 15);
        $logs = $query->with('user')->latest()->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $logs
        ]);
    }

    /**
     * Get IP whitelist entries.
     */
    public function ipWhitelist(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'is_active' => 'nullable|boolean',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $query = IpWhitelist::query();

        if ($request->has('is_active')) {
            $query->where('is_active', $request->is_active);
        }

        $perPage = $request->input('per_page', 15);
        $whitelist = $query->with('addedBy')->latest()->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $whitelist
        ]);
    }

    /**
     * Add an IP address to the whitelist.
     */
    public function addToWhitelist(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'ip_address' => 'required|ip',
            'description' => 'nullable|string|max:255',
            'expires_in_days' => 'nullable|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $ipWhitelist = $this->securityService->addIpToWhitelist(
                $request->ip_address,
                Auth::user(),
                $request->description,
                $request->expires_in_days
            );

            return response()->json([
                'success' => true,
                'data' => $ipWhitelist
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to add IP address to whitelist',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove an IP address from the whitelist.
     */
    public function removeFromWhitelist(string $ipAddress)
    {
        try {
            $result = $this->securityService->removeIpFromWhitelist($ipAddress);

            if ($result) {
                return response()->json([
                    'success' => true,
                    'message' => 'IP address removed from whitelist'
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'IP address not found in whitelist'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to remove IP address from whitelist',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get suspicious activity report.
     */
    public function suspiciousActivity(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $query = SecurityLog::where('event_type', 'suspicious_activity');

        if ($request->has('start_date')) {
            $query->where('created_at', '>=', $request->start_date);
        }

        if ($request->has('end_date')) {
            $query->where('created_at', '<=', $request->end_date);
        }

        $perPage = $request->input('per_page', 15);
        $activities = $query->with('user')->latest()->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $activities
        ]);
    }

    /**
     * Get device usage statistics.
     */
    public function deviceStats()
    {
        $stats = $this->securityService->getSecurityStats()['device_types'];

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }
} 