<?php

namespace Tests\Unit;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChangeUserPasswordCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_change_user_password_command(): void
    {
        $user = User::factory()->create(['name' => 'Test User', 'email' => 'test@example.com']);

        $this->artisan('user:change-password', ['email' => 'test@example.com'])
            ->expectsOutput('Password changed successfully!')
            ->expectsOutput('Name: Test User')
            ->expectsOutput('Email: test@example.com')
            ->assertExitCode(0);
    }
}
