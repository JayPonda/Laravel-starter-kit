<?php

namespace Tests\Unit;

use App\Services\UserService;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserServiceTest extends TestCase
{
    use DatabaseMigrations;

    private UserService $userService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->userService = new UserService();
    }

    public function test_create_user(): void
    {
        $result = $this->userService->createUser('John Doe', 'john@example.com');

        $this->assertInstanceOf(User::class, $result['user']);
        $this->assertEquals('John Doe', $result['user']->name);
        $this->assertEquals('john@example.com', $result['user']->email);
        $this->assertNotNull($result['password']);
        $this->assertTrue(Hash::check($result['password'], $result['user']->password));
    }

    public function test_register_user(): void
    {
        $user = $this->userService->register('Jane Doe', 'jane@example.com', 'password123');

        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals('Jane Doe', $user->name);
        $this->assertEquals('jane@example.com', $user->email);
        $this->assertTrue(Hash::check('password123', $user->password));
    }

    public function test_login_with_valid_credentials(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('password123'),
        ]);

        $loggedInUser = $this->userService->login($user->email, 'password123');

        $this->assertEquals($user->id, $loggedInUser->id);
    }

    public function test_login_with_invalid_credentials(): void
    {
        $user = User::factory()->create();

        $this->expectException(\Illuminate\Validation\ValidationException::class);
        $this->userService->login($user->email, 'wrongpassword');
    }

    public function test_change_password(): void
    {
        $user = User::factory()->create();

        $result = $this->userService->changePassword($user->email);

        $this->assertInstanceOf(User::class, $result['user']);
        $this->assertNotNull($result['password']);
        $this->assertTrue(Hash::check($result['password'], $result['user']->fresh()->password));
    }
}