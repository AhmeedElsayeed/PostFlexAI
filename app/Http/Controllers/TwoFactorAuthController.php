<?php

namespace App\Http\Controllers;

use App\Services\TwoFactorAuthService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TwoFactorAuthController extends Controller
{
    protected $twoFactorAuthService;

    public function __construct(TwoFactorAuthService $twoFactorAuthService)
    {
        $this->twoFactorAuthService = $twoFactorAuthService;
    }

    /**
     * Enable 2FA for the authenticated user
     */
    public function enable(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'method' => 'required|in:authenticator,sms,whatsapp',
            'phone_number' => 'required_if:method,sms,whatsapp|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $user = $request->user();

        if ($request->has('phone_number')) {
            $user->phone_number = $request->phone_number;
            $user->whatsapp_enabled = $request->method === 'whatsapp';
            $user->save();
        }

        $result = $this->twoFactorAuthService->enable($user, $request->method);

        return response()->json([
            'success' => true,
            'data' => $result
        ]);
    }

    /**
     * Verify 2FA code
     */
    public function verify(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'code' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $user = $request->user();
        $method = $user->two_factor_method;

        $verified = match($method) {
            'authenticator' => $this->twoFactorAuthService->verify($user, $request->code),
            'sms' => $this->twoFactorAuthService->verifySmsCode($user, $request->code),
            'whatsapp' => $this->twoFactorAuthService->verifyWhatsappCode($user, $request->code),
            default => false,
        };

        if (!$verified) {
            return response()->json([
                'success' => false,
                'message' => 'رمز التحقق غير صحيح'
            ], 422);
        }

        $user->two_factor_confirmed_at = now();
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'تم التحقق بنجاح'
        ]);
    }

    /**
     * Verify recovery code
     */
    public function verifyRecoveryCode(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'code' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $user = $request->user();
        $verified = $this->twoFactorAuthService->verifyRecoveryCode($user, $request->code);

        if (!$verified) {
            return response()->json([
                'success' => false,
                'message' => 'رمز الاسترداد غير صحيح'
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'تم التحقق بنجاح'
        ]);
    }

    /**
     * Disable 2FA for the authenticated user
     */
    public function disable(Request $request)
    {
        $user = $request->user();
        $this->twoFactorAuthService->disable($user);

        return response()->json([
            'success' => true,
            'message' => 'تم تعطيل المصادقة الثنائية'
        ]);
    }

    /**
     * Send 2FA code via SMS
     */
    public function sendSmsCode(Request $request)
    {
        $user = $request->user();

        if ($user->two_factor_method !== 'sms') {
            return response()->json([
                'success' => false,
                'message' => 'طريقة المصادقة الثنائية غير صحيحة'
            ], 422);
        }

        $sent = $this->twoFactorAuthService->sendSmsCode($user);

        if (!$sent) {
            return response()->json([
                'success' => false,
                'message' => 'فشل إرسال رمز التحقق'
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'تم إرسال رمز التحقق'
        ]);
    }

    /**
     * Send 2FA code via WhatsApp
     */
    public function sendWhatsappCode(Request $request)
    {
        $user = $request->user();

        if ($user->two_factor_method !== 'whatsapp') {
            return response()->json([
                'success' => false,
                'message' => 'طريقة المصادقة الثنائية غير صحيحة'
            ], 422);
        }

        $sent = $this->twoFactorAuthService->sendWhatsappCode($user);

        if (!$sent) {
            return response()->json([
                'success' => false,
                'message' => 'فشل إرسال رمز التحقق'
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'تم إرسال رمز التحقق'
        ]);
    }
} 