<?php

namespace Tests\Unit\Controllers;

use App\Http\Controllers\Auth\AuthController;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Session\Store;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Mockery;
use Tests\TestCase;

class AuthControllerTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    public function test_login_returns_authenticated_user_and_regenerates_session(): void
    {
        $user = new User();
        $user->forceFill([
            'id' => 1,
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'role' => 'student',
        ]);

        $session = Mockery::mock(Store::class);
        $session->shouldReceive('regenerate')->once();

        $request = Mockery::mock(Request::class);
        $request->shouldReceive('validate')->once()->with([
            'email' => 'required|email',
            'password' => 'required|string',
            'remember' => 'boolean',
        ]);
        $request->shouldReceive('only')->once()->with('email', 'password')->andReturn([
            'email' => 'jane@example.com',
            'password' => 'secret',
        ]);
        $request->shouldReceive('boolean')->once()->with('remember')->andReturn(true);
        $request->shouldReceive('session')->once()->andReturn($session);

        Auth::shouldReceive('attempt')->once()->with([
            'email' => 'jane@example.com',
            'password' => 'secret',
        ], true)->andReturnTrue();
        Auth::shouldReceive('user')->atLeast()->once()->andReturn($user);

        $response = (new AuthController())->login($request);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame([
            'message' => 'Success',
            'user' => [
                'id' => 1,
                'name' => 'Jane Doe',
                'email' => 'jane@example.com',
                'role' => 'student',
            ],
        ], $response->getData(true));
    }

    public function test_login_throws_validation_exception_for_invalid_credentials(): void
    {
        $request = Mockery::mock(Request::class);
        $request->shouldReceive('validate')->once()->with([
            'email' => 'required|email',
            'password' => 'required|string',
            'remember' => 'boolean',
        ]);
        $request->shouldReceive('only')->once()->with('email', 'password')->andReturn([
            'email' => 'jane@example.com',
            'password' => 'wrong-password',
        ]);
        $request->shouldReceive('boolean')->once()->with('remember')->andReturnFalse();

        Auth::shouldReceive('attempt')->once()->with([
            'email' => 'jane@example.com',
            'password' => 'wrong-password',
        ], false)->andReturnFalse();

        try {
            (new AuthController())->login($request);
            $this->fail('A validation exception should be thrown when authentication fails.');
        } catch (ValidationException $exception) {
            $this->assertSame([
                'email' => ['The provided credentials are incorrect.'],
            ], $exception->errors());
        }
    }

    public function test_logout_invalidates_session_and_regenerates_csrf_token(): void
    {
        $session = Mockery::mock(Store::class);
        $session->shouldReceive('invalidate')->once();
        $session->shouldReceive('regenerateToken')->once();

        $request = Mockery::mock(Request::class);
        $request->shouldReceive('session')->atLeast()->once()->andReturn($session);

        Auth::shouldReceive('logout')->once();

        $response = (new AuthController())->logout($request);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(['message' => 'Success'], $response->getData(true));
    }

    public function test_me_returns_the_authenticated_user(): void
    {
        $user = new User();
        $user->forceFill([
            'id' => 1,
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'role' => 'student',
        ]);

        $request = Mockery::mock(Request::class);
        $request->shouldReceive('user')->once()->andReturn($user);
        Auth::shouldReceive('user')->once()->andReturn($user);

        $response = (new AuthController())->me($request);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame([
            'user' => [
                'id' => 1,
                'name' => 'Jane Doe',
                'email' => 'jane@example.com',
                'role' => 'student',
            ],
        ], $response->getData(true));
    }
}
