<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Rules\StrongPassword;
use App\Services\LoginAttemptService;
use App\Services\SecurityService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    protected $loginAttemptService;
    protected $securityService;

    public function __construct(LoginAttemptService $loginAttemptService, SecurityService $securityService)
    {
        $this->loginAttemptService = $loginAttemptService;
        $this->securityService = $securityService;
    }

    /**
     * Register a new user.
     */
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => ['required', 'string', 'confirmed', new StrongPassword],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'success' => true,
            'data' => [
                'user' => $user,
                'token' => $token,
            ]
        ], 201);
    }

    /**
     * Login a user.
     */
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            $this->loginAttemptService->recordAttempt($user, $request, false);
            
            throw ValidationException::withMessages([
                'email' => ['بيانات الاعتماد المقدمة غير صحيحة.'],
            ]);
        }

        // Check for suspicious activity
        if ($this->loginAttemptService->isSuspicious($user, $request)) {
            $this->securityService->logEvent(
                'suspicious_login',
                'blocked',
                $user,
                $request,
                ['reason' => 'suspicious_activity'],
                'تم حظر تسجيل الدخول بسبب نشاط مشبوه'
            );
            
            throw ValidationException::withMessages([
                'email' => ['تم حظر تسجيل الدخول مؤقتًا. يرجى المحاولة لاحقًا.'],
            ]);
        }

        $this->loginAttemptService->recordAttempt($user, $request, true);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'success' => true,
            'data' => [
                'user' => $user,
                'token' => $token,
            ]
        ]);
    }

    /**
     * Logout a user.
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'تم تسجيل الخروج بنجاح'
        ]);
    }

    /**
     * Logout from all devices.
     */
    public function logoutFromAllDevices(Request $request)
    {
        $request->user()->tokens()->delete();

        return response()->json([
            'success' => true,
            'message' => 'تم تسجيل الخروج من جميع الأجهزة بنجاح'
        ]);
    }

    /**
     * Get the authenticated user.
     */
    public function user(Request $request)
    {
        return response()->json([
            'success' => true,
            'data' => $request->user()
        ]);
    }

    /**
     * Refresh the token.
     */
    public function refresh(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        $token = $request->user()->createToken('auth_token')->plainTextToken;

        return response()->json([
            'success' => true,
            'data' => [
                'token' => $token,
            ]
        ]);
    }

    /**
     * Send a password reset link.
     */
    public function forgotPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|string|email',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'لم نتمكن من العثور على مستخدم بهذا البريد الإلكتروني.'
            ], 404);
        }

        // Generate password reset token
        $token = \Str::random(60);
        \DB::table('password_resets')->insert([
            'email' => $user->email,
            'token' => Hash::make($token),
            'created_at' => now(),
        ]);

        // Send password reset email
        // TODO: Implement email sending

        return response()->json([
            'success' => true,
            'message' => 'تم إرسال رابط إعادة تعيين كلمة المرور إلى بريدك الإلكتروني.'
        ]);
    }

    /**
     * Reset the password.
     */
    public function resetPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|string|email',
            'token' => 'required|string',
            'password' => ['required', 'string', 'confirmed', new StrongPassword],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $reset = \DB::table('password_resets')
            ->where('email', $request->email)
            ->first();

        if (!$reset) {
            return response()->json([
                'success' => false,
                'message' => 'رمز إعادة تعيين كلمة المرور غير صالح.'
            ], 400);
        }

        if (!Hash::check($request->token, $reset->token)) {
            return response()->json([
                'success' => false,
                'message' => 'رمز إعادة تعيين كلمة المرور غير صالح.'
            ], 400);
        }

        $user = User::where('email', $request->email)->first();
        $user->password = Hash::make($request->password);
        $user->save();

        \DB::table('password_resets')
            ->where('email', $request->email)
            ->delete();

        return response()->json([
            'success' => true,
            'message' => 'تم إعادة تعيين كلمة المرور بنجاح.'
        ]);
    }

    /**
     * Check if the user is authenticated.
     */
    public function check(Request $request)
    {
        return response()->json([
            'success' => true,
            'data' => [
                'authenticated' => Auth::check(),
                'user' => $request->user(),
            ]
        ]);
    }
} 