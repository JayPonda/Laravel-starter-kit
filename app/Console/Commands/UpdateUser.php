<?php

namespace App\Console\Commands;

use App\Services\UserService;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class UpdateUser extends Command
{
    protected $signature = 'user:update {email : The email of the user to update}';

    protected $description = 'Reset a user\'s password to a new temporary password';

    public function __construct(
        private UserService $userService
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $email = $this->argument('email');

        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->error('A valid email is required.');
            return Command::FAILURE;
        }

        try {
            $result = $this->userService->changePassword($email);
        } catch (ModelNotFoundException $e) {
            $this->error("No user found with the email {$email}.");
            return Command::FAILURE;
        }

        $this->info('User password updated successfully!');
        $this->info("Name: {$result['user']->name}");
        $this->info("Email: {$result['user']->email}");
        $this->info("Temporary Password: {$result['password']}");

        return Command::SUCCESS;
    }
}
