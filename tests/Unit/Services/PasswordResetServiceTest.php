<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Models\User;
use App\Models\PasswordReset;
use App\Services\PasswordResetService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Foundation\Testing\RefreshDatabase;

class PasswordResetServiceTest extends TestCase
{
    use RefreshDatabase;

    protected $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new PasswordResetService();
    }

    public function test_can_request_password_reset()
    {
        Mail::fake();
        
        $user = User::factory()->create();
        
        $result = $this->service->requestReset($user->email);
        
        $this->assertTrue($result);
        $this->assertDatabaseHas('password_resets', [
            'user_id' => $user->id,
            'used' => false
        ]);
        
        Mail::assertSent(function ($mail) use ($user) {
            return $mail->hasTo($user->email);
        });
    }

    public function test_can_reset_password()
    {
        $user = User::factory()->create();
        $reset = PasswordReset::generateToken($user);
        
        $result = $this->service->resetPassword($reset->token, 'newpassword123');
        
        $this->assertTrue($result);
        $this->assertTrue(Hash::check('newpassword123', $user->fresh()->password));
        $this->assertTrue($reset->fresh()->used);
    }

    public function test_cannot_reset_with_invalid_token()
    {
        $result = $this->service->resetPassword('invalid-token', 'newpassword123');
        
        $this->assertFalse($result);
    }

    public function test_cannot_reset_with_expired_token()
    {
        $user = User::factory()->create();
        $reset = PasswordReset::generateToken($user);
        $reset->update(['expires_at' => now()->subDay()]);
        
        $result = $this->service->resetPassword($reset->token, 'newpassword123');
        
        $this->assertFalse($result);
    }
} 