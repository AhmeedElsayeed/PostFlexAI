<?php

namespace App\Services;

use App\Models\User;
use PragmaRX\Google2FA\Google2FA;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class TwoFactorAuthService
{
    protected $google2fa;
    protected $securityService;

    public function __construct(SecurityService $securityService)
    {
        $this->google2fa = new Google2FA();
        $this->securityService = $securityService;
    }

    /**
     * Enable 2FA for a user
     *
     * @param User $user
     * @param string $method
     * @return array
     */
    public function enable(User $user, string $method = 'authenticator')
    {
        // Generate secret key
        $secret = $this->google2fa->generateSecretKey();

        // Generate recovery codes
        $recoveryCodes = $this->generateRecoveryCodes();

        // Store in user record
        $user->two_factor_secret = $secret;
        $user->two_factor_recovery_codes = json_encode($recoveryCodes);
        $user->two_factor_method = $method;
        $user->two_factor_enabled = true;
        $user->save();

        // Log the event
        $this->securityService->logEvent(
            '2fa_enabled',
            'success',
            $user,
            request(),
            ['method' => $method],
            'تم تفعيل المصادقة الثنائية'
        );

        return [
            'secret' => $secret,
            'recovery_codes' => $recoveryCodes,
            'qr_code' => $this->google2fa->getQRCodeUrl(
                config('app.name'),
                $user->email,
                $secret
            )
        ];
    }

    /**
     * Verify 2FA code
     *
     * @param User $user
     * @param string $code
     * @return bool
     */
    public function verify(User $user, string $code)
    {
        if (!$user->two_factor_enabled) {
            return false;
        }

        $valid = $this->google2fa->verifyKey($user->two_factor_secret, $code);

        if ($valid) {
            $this->securityService->logEvent(
                '2fa_verified',
                'success',
                $user,
                request(),
                ['method' => $user->two_factor_method],
                'تم التحقق من رمز المصادقة الثنائية بنجاح'
            );
        } else {
            $this->securityService->logEvent(
                '2fa_failed',
                'failed',
                $user,
                request(),
                ['method' => $user->two_factor_method, 'code' => $code],
                'فشل التحقق من رمز المصادقة الثنائية'
            );
        }

        return $valid;
    }

    /**
     * Verify recovery code
     *
     * @param User $user
     * @param string $code
     * @return bool
     */
    public function verifyRecoveryCode(User $user, string $code)
    {
        if (!$user->two_factor_enabled) {
            return false;
        }

        $recoveryCodes = json_decode($user->two_factor_recovery_codes, true);
        
        if (in_array($code, $recoveryCodes)) {
            // Remove used recovery code
            $recoveryCodes = array_diff($recoveryCodes, [$code]);
            $user->two_factor_recovery_codes = json_encode(array_values($recoveryCodes));
            $user->save();

            $this->securityService->logEvent(
                '2fa_recovery_used',
                'success',
                $user,
                request(),
                ['code' => $code],
                'تم استخدام رمز استرداد المصادقة الثنائية'
            );

            return true;
        }

        $this->securityService->logEvent(
            '2fa_recovery_failed',
            'failed',
            $user,
            request(),
            ['code' => $code],
            'فشل استخدام رمز استرداد المصادقة الثنائية'
        );

        return false;
    }

    /**
     * Disable 2FA for a user
     *
     * @param User $user
     * @return bool
     */
    public function disable(User $user)
    {
        $user->two_factor_secret = null;
        $user->two_factor_recovery_codes = null;
        $user->two_factor_confirmed_at = null;
        $user->two_factor_enabled = false;
        $user->save();

        $this->securityService->logEvent(
            '2fa_disabled',
            'success',
            $user,
            request(),
            [],
            'تم تعطيل المصادقة الثنائية'
        );

        return true;
    }

    /**
     * Generate new recovery codes
     *
     * @param int $count
     * @return array
     */
    protected function generateRecoveryCodes(int $count = 8)
    {
        $codes = [];
        for ($i = 0; $i < $count; $i++) {
            $codes[] = Str::random(10);
        }
        return $codes;
    }

    /**
     * Send 2FA code via SMS
     *
     * @param User $user
     * @return bool
     */
    public function sendSmsCode(User $user)
    {
        if ($user->two_factor_method !== 'sms' || !$user->phone_number) {
            return false;
        }

        $code = $this->generateSmsCode();
        Cache::put('2fa_sms_' . $user->id, $code, now()->addMinutes(5));

        // TODO: Implement SMS sending logic
        // For now, just log it
        Log::info('2FA SMS code for user ' . $user->id . ': ' . $code);

        $this->securityService->logEvent(
            '2fa_sms_sent',
            'success',
            $user,
            request(),
            [],
            'تم إرسال رمز المصادقة الثنائية عبر SMS'
        );

        return true;
    }

    /**
     * Send 2FA code via WhatsApp
     *
     * @param User $user
     * @return bool
     */
    public function sendWhatsappCode(User $user)
    {
        if ($user->two_factor_method !== 'whatsapp' || !$user->phone_number || !$user->whatsapp_enabled) {
            return false;
        }

        $code = $this->generateSmsCode();
        Cache::put('2fa_whatsapp_' . $user->id, $code, now()->addMinutes(5));

        // TODO: Implement WhatsApp sending logic
        // For now, just log it
        Log::info('2FA WhatsApp code for user ' . $user->id . ': ' . $code);

        $this->securityService->logEvent(
            '2fa_whatsapp_sent',
            'success',
            $user,
            request(),
            [],
            'تم إرسال رمز المصادقة الثنائية عبر WhatsApp'
        );

        return true;
    }

    /**
     * Generate SMS code
     *
     * @return string
     */
    protected function generateSmsCode()
    {
        return str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    }

    /**
     * Verify SMS code
     *
     * @param User $user
     * @param string $code
     * @return bool
     */
    public function verifySmsCode(User $user, string $code)
    {
        $storedCode = Cache::get('2fa_sms_' . $user->id);
        
        if ($storedCode && $storedCode === $code) {
            Cache::forget('2fa_sms_' . $user->id);
            
            $this->securityService->logEvent(
                '2fa_sms_verified',
                'success',
                $user,
                request(),
                [],
                'تم التحقق من رمز المصادقة الثنائية عبر SMS'
            );
            
            return true;
        }

        $this->securityService->logEvent(
            '2fa_sms_failed',
            'failed',
            $user,
            request(),
            ['code' => $code],
            'فشل التحقق من رمز المصادقة الثنائية عبر SMS'
        );

        return false;
    }

    /**
     * Verify WhatsApp code
     *
     * @param User $user
     * @param string $code
     * @return bool
     */
    public function verifyWhatsappCode(User $user, string $code)
    {
        $storedCode = Cache::get('2fa_whatsapp_' . $user->id);
        
        if ($storedCode && $storedCode === $code) {
            Cache::forget('2fa_whatsapp_' . $user->id);
            
            $this->securityService->logEvent(
                '2fa_whatsapp_verified',
                'success',
                $user,
                request(),
                [],
                'تم التحقق من رمز المصادقة الثنائية عبر WhatsApp'
            );
            
            return true;
        }

        $this->securityService->logEvent(
            '2fa_whatsapp_failed',
            'failed',
            $user,
            request(),
            ['code' => $code],
            'فشل التحقق من رمز المصادقة الثنائية عبر WhatsApp'
        );

        return false;
    }
} 