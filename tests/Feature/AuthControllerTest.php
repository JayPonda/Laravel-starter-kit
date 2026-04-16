<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register(): void
    {
        $response = $this->postJson('/api/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'user',
                'token',
            ]);

        $this->assertDatabaseHas('users', [
            'email' => 'test@example.com',
        ]);
    }

    public function test_user_can_login(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('password123'),
        ]);

        $response = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'password123',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'user',
                'token',
            ]);
    }

    public function test_login_fails_with_invalid_credentials(): void
    {
        $user = User::factory()->create();

        $response = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(422);
    }

    public function test_authenticated_user_can_logout(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('auth-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
        ])->postJson('/api/logout');

        $response->assertStatus(200);
        $this->assertCount(0, $user->fresh()->tokens);
    }

    public function test_authenticated_user_can_get_me(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('auth-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
        ])->getJson('/api/me');

        $response->assertStatus(200)
            ->assertJson([
                'email' => $user->email,
            ]);
    }

    public function test_user_can_reset_password(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('old-password'),
        ]);

        $response = $this->postJson('/api/reset-password', [
            'email' => $user->email,
            'old_password' => 'old-password',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'temporary_password',
            ]);

        $this->assertTrue(Hash::check($response->json('temporary_password'), $user->fresh()->password));
    }

    public function test_reset_password_validation_fails(): void
    {
        $response = $this->postJson('/api/reset-password', [
            'email' => 'nonexistent@example.com',
            'old_password' => 'some-password',
        ]);

        $response->assertStatus(422);
    }
}
