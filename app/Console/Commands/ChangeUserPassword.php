<?php

namespace App\Console\Commands;

use App\Services\UserService;
use Illuminate\Console\Command;

class ChangeUserPassword extends Command
{
    protected $signature = 'user:change-password {email : The email of the user}';

    protected $description = 'Change password for a user by email';

    public function __construct(
        private UserService $userService
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $email = $this->argument('email');

        $result = $this->userService->changePassword($email);

        $this->info('Password changed successfully!');
        $this->info("Name: {$result['user']->name}");
        $this->info("Email: {$result['user']->email}");
        $this->info("New Password: {$result['password']}");

        return Command::SUCCESS;
    }
}
