<?php

function runCommand($command, $description)
{
    echo "\n>>> $description\n";
    echo "Executing: $command\n";
    passthru($command, $return_var);
    if ($return_var !== 0) {
        echo "Error: Command failed with exit code $return_var\n";
        exit($return_var);
    }
}

// Parse options
$options = getopt("i");
$shouldInstall = isset($options['i']);

// We are now inside the template directory
$templateDir = __DIR__;

// 1. & 2. Generate MySQL and Redis configs
// These need to run first as Docker depends on them (via volumes)
runCommand('php artisan db:generate-sql', 'Generating MySQL Config');
runCommand('php artisan redis:generate-config', 'Generating Redis Config');

// 3. Start Docker
runCommand('docker compose up -d', 'Starting Docker Containers');

// Wait for MySQL to be ready
echo "\n>>> Waiting for MySQL to be ready...\n";
$maxAttempts = 30;
$attempt = 0;
$ready = false;
while ($attempt < $maxAttempts) {
    // We use 'mysqladmin ping' to check if the server is alive
    // The root password is taken from the same logic as docker-compose.yml: DB_PASSWORD or 'root'
    $dbPassword = getenv('DB_PASSWORD') ?: 'password';
    $checkCommand = "docker compose exec -T mysql mysqladmin ping -u root -p$dbPassword 2>/dev/null";
    exec($checkCommand, $output, $returnVar);

    if ($returnVar === 0) {
        $ready = true;
        break;
    }
    $attempt++;
    sleep(2);
    echo '.';
}

if (! $ready) {
    echo "\nError: MySQL did not become ready in time.\n";
    exit(1);
}
echo "\nMySQL is ready!\n";

// Execute the generated SQL file directly
runCommand("docker compose exec -T mysql mysql -u root -p$dbPassword < storage/database.sql", 'Applying SQL Configuration directly to MySQL');

// 4. Conditional Install
if ($shouldInstall) {
    runCommand('composer install', 'Installing Composer Dependencies');
    runCommand('npm install', 'Installing NPM Dependencies');
} else {
    echo "\n>>> Skipping installations (use -i to install)\n";
}

// 5. Artisan storage:link
runCommand('php artisan storage:link', 'Linking Storage');

// 6. Artisan key:generate
runCommand('php artisan key:generate', 'Generating App Key');

// 7. Artisan migrate
runCommand('php artisan migrate', 'Running Database Migrations');

// 8. Artisan pint
runCommand('./vendor/bin/pint', 'Running Laravel Pint (Linting)');

// 9. Artisan test
runCommand('php artisan test', 'Running Tests');

// 10. Artisan serve
runCommand("php artisan serve", "Starting Laravel Development Server on port $port");
