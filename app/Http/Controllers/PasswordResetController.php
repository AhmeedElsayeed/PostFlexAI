<?php

namespace App\Http\Controllers;

use App\Services\PasswordResetService;
use Illuminate\Http\Request;

class PasswordResetController extends Controller
{
    protected $passwordResetService;

    public function __construct(PasswordResetService $passwordResetService)
    {
        $this->passwordResetService = $passwordResetService;
    }

    public function requestReset(Request $request)
    {
        $request->validate([
            'email' => 'required|email'
        ]);

        $success = $this->passwordResetService->requestReset($request->email);

        return response()->json([
            'message' => $success 
                ? 'Password reset instructions sent to your email'
                : 'If the email exists, reset instructions will be sent'
        ]);
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'token' => 'required|string',
            'password' => 'required|string|min:8|confirmed'
        ]);

        $success = $this->passwordResetService->resetPassword(
            $request->token,
            $request->password
        );

        if (!$success) {
            return response()->json([
                'message' => 'Invalid or expired reset token'
            ], 422);
        }

        return response()->json([
            'message' => 'Password reset successfully'
        ]);
    }
} 