<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use App\Notifications\SystemErrorNotification;
use Throwable;

class Handler extends ExceptionHandler
{
    protected $dontReport = [
        //
    ];

    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            $this->logError($e);
            $this->notifyAdmins($e);
        });
    }

    private function logError(Throwable $e)
    {
        $context = [
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString(),
            'user_id' => auth()->id(),
            'url' => request()->fullUrl(),
            'method' => request()->method(),
            'ip' => request()->ip(),
        ];

        Log::error($e->getMessage(), $context);
    }

    private function notifyAdmins(Throwable $e)
    {
        if ($this->shouldNotify($e)) {
            $admins = \App\Models\User::role('admin')->get();
            Notification::send($admins, new SystemErrorNotification($e));
        }
    }

    private function shouldNotify(Throwable $e)
    {
        // Only notify for critical errors
        return $e instanceof \Exception && 
               !$e instanceof \Illuminate\Auth\AuthenticationException &&
               !$e instanceof \Illuminate\Validation\ValidationException;
    }
} 