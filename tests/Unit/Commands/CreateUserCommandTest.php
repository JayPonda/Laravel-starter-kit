<?php

namespace Tests\Unit\Commands;

use App\Models\User;
use App\Services\UserService;
use Illuminate\Database\UniqueConstraintViolationException;
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
                    'user' => new User(['name' => 'John Doe', 'email' => 'john@example.com']),
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

    public function test_fails_with_empty_name(): void
    {
        $this->artisan('user:create', [
            'name' => '',
            'email' => 'john@example.com',
        ])
            ->expectsOutput('Name is required.')
            ->assertExitCode(1);
    }

    public function test_fails_with_invalid_email(): void
    {
        $this->artisan('user:create', [
            'name' => 'John Doe',
            'email' => 'not-an-email',
        ])
            ->expectsOutput('A valid email is required.')
            ->assertExitCode(1);
    }

    public function test_fails_on_duplicate_email(): void
    {
        $this->mock(UserService::class, function ($mock) {
            $mock->shouldReceive('createUser')
                ->with('John Doe', 'john@example.com')
                ->andThrow(new UniqueConstraintViolationException(
                    'mysql', 'INSERT...', [],
                    new \Exception()
                ));
        });

        $this->artisan('user:create', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ])
            ->expectsOutput('A user with the email john@example.com already exists.')
            ->assertExitCode(1);
    }
}
