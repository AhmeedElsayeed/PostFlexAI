<?php

namespace Tests\Unit\Middleware;

use Tests\TestCase;
use App\Http\Middleware\SecureSession;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Foundation\Testing\RefreshDatabase;

class SecureSessionTest extends TestCase
{
    use RefreshDatabase;

    protected $middleware;
    protected $request;

    protected function setUp(): void
    {
        parent::setUp();
        $this->middleware = new SecureSession();
        $this->request = Request::create('/test');
    }

    public function test_sets_secure_session_parameters()
    {
        $this->middleware->handle($this->request, function ($request) {
            $this->assertTrue(config('session.secure'));
            $this->assertTrue(config('session.http_only'));
            $this->assertEquals('lax', config('session.same_site'));
        });
    }

    public function test_regenerates_session_id()
    {
        $oldId = Session::getId();
        
        $this->middleware->handle($this->request, function ($request) {
            // Session should be regenerated
        });

        $this->assertNotEquals($oldId, Session::getId());
    }

    public function test_expires_session_after_timeout()
    {
        // Set last activity to 31 minutes ago
        Session::put('last_activity', time() - 1860);
        
        $response = $this->middleware->handle($this->request, function ($request) {
            return response()->json(['message' => 'success']);
        });

        $this->assertEquals(401, $response->getStatusCode());
        $this->assertEquals('Session expired. Please login again.', json_decode($response->getContent())->message);
    }

    public function test_updates_last_activity()
    {
        $this->middleware->handle($this->request, function ($request) {
            // Middleware should update last_activity
        });

        $this->assertTrue(Session::has('last_activity'));
        $this->assertIsInt(Session::get('last_activity'));
    }
} 