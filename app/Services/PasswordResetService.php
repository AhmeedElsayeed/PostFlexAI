<?php

namespace App\Services;

use App\Models\User;
use App\Models\PasswordReset;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class PasswordResetService
{
    public function requestReset(string $email)
    {
        $user = User::where('email', $email)->first();
        
        if (!$user) {
            return false;
        }

        $reset = PasswordReset::generateToken($user);
        
        // Send reset email
        Mail::send('emails.password-reset', [
            'user' => $user,
            'reset' => $reset
        ], function($message) use ($user) {
            $message->to($user->email)
                   ->subject('Reset Your Password');
        });

        return true;
    }

    public function resetPassword(string $token, string $password)
    {
        $reset = PasswordReset::where('token', $token)
            ->where('used', false)
            ->where('expires_at', '>', now())
            ->first();

        if (!$reset) {
            return false;
        }

        $user = $reset->user;
        $user->update([
            'password' => Hash::make($password)
        ]);

        $reset->update(['used' => true]);

        // Invalidate all existing sessions
        $user->tokens()->delete();

        return true;
    }
} 