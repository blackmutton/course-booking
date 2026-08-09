<?php

namespace Tests\Feature\Controllers;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class AuthControllerTest extends TestCase
{
    use RefreshDatabase, WithFaker;
    public function setUp(): void
    {
        parent::setUp();
        // 在這裡可以進行測試前的初始化操作，例如建立測試資料
        User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password'),
            'name' => 'Test User',
            'role' => UserRole::STUDENT,
            'email_verified_token' => Str::uuid()->toString(),
            'email_token_expiration' => now()->addMinutes(30),
        ]);
    }
    /**
     * A basic feature test example.
     */
    public function test_login(): void
    {
        $response = $this->post('/api/login', [
            'email' => 'test@example.com',
            'password' => 'password',
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseCount('personal_access_tokens', 1);
    }

    public function test_logout(): void
    {
        // Login
        $loginResponse = $this->postJson('/api/login', [
            'email' => 'test@example.com',
            'password' => 'password',
        ]);

        $loginResponse->assertStatus(200);

        // 取得 Sanctum token
        $token = $loginResponse->json('token');

        $this->assertNotEmpty($token);
        $this->assertDatabaseCount('personal_access_tokens', 1);

        // Logout，帶上 Bearer Token
        $response = $this->withToken($token)
            ->postJson('/api/logout');

        $response->assertStatus(200);

        // Token 應該被刪除
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }
}