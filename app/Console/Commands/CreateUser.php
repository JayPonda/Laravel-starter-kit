<?php

namespace App\Console\Commands;

use App\Services\UserService;
use Illuminate\Console\Command;
use Illuminate\Database\UniqueConstraintViolationException;

class CreateUser extends Command
{
    protected $signature = 'user:create {name : The name of the user} {email : The email of the user}';

    protected $description = 'Create a new user with a temporary password';

    public function __construct(
        private UserService $userService
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $name = $this->argument('name');
        $email = $this->argument('email');

        if (empty($name)) {
            $this->error('Name is required.');
            return Command::FAILURE;
        }

        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->error('A valid email is required.');
            return Command::FAILURE;
        }

        try {
            $result = $this->userService->createUser($name, $email);
        } catch (UniqueConstraintViolationException $e) {
            $this->error("A user with the email {$email} already exists.");
            return Command::FAILURE;
        }

        $this->info('User created successfully!');
        $this->info("Name: {$result['user']->name}");
        $this->info("Email: {$result['user']->email}");
        $this->info("Temporary Password: {$result['password']}");

        return Command::SUCCESS;
    }
}
