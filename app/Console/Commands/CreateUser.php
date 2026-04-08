<?php

namespace App\Console\Commands;

use App\Services\UserService;
use Illuminate\Console\Command;

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

        $result = $this->userService->createUser($name, $email);

        $this->info("User created successfully!");
        $this->info("Name: {$result['user']->name}");
        $this->info("Email: {$result['user']->email}");
        $this->info("Temporary Password: {$result['password']}");

        return Command::SUCCESS;
    }
}
