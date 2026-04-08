<?php

namespace Tests\Unit;

use App\Services\UserService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreateUserCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_user_command(): void
    {
        $this->mock(UserService::class, function ($mock) {
            $mock->shouldReceive('createUser')
                ->with('John Doe', 'john@example.com')
                ->andReturn([
                    'user' => new \App\Models\User(['name' => 'John Doe', 'email' => 'john@example.com']),
                    'password' => 'temppassword123',
                ]);
        });

        $this->artisan('user:create', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ])
            ->expectsOutput('User created successfully!')
            ->expectsOutput('Name: John Doe')
            ->expectsOutput('Email: john@example.com')
            ->expectsOutput('Temporary Password: temppassword123')
            ->assertExitCode(0);
    }
}