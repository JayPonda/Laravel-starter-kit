<?php

require_once __DIR__.'/setup/utility.php';

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

$SAIL = './vendor/bin/sail';

// Parse options for installation
$options = getopt('i');
$shouldInstall = isset($options['i']);

// 1. Ensure .env exists
if (!file_exists('.env')) {
    echo ">>> .env not found. Copying .env.example...\n";
    copy('.env.example', '.env');
}

// 2. Load environment
$env = getAppEnv();

// 3. Optional Local Install (required if vendor/ is missing)
if ($shouldInstall) {
    runCommand('composer install', 'Installing Composer Dependencies locally');
    runCommand('npm install', 'Installing NPM Dependencies locally');
}

// 4. Generate MySQL and Redis configs (Docker volumes depend on these)
runCommand('php setup/generate-db-sql.php', 'Generating MySQL Config');
runCommand('php setup/generate-redis-conf.php', 'Generating Redis Config');

// 5. Start Sail
runCommand("$SAIL up -d", 'Starting Laravel Sail Containers');

// 6. Wait for MySQL to be ready
echo "\n>>> Waiting for MySQL to be ready...\n";
$maxAttempts = 30;
$attempt = 0;
$ready = false;
while ($attempt < $maxAttempts) {
    // Check if we can connect to the DB via Artisan
    $checkCommand = "$SAIL artisan db:show > /dev/null 2>&1";
    exec($checkCommand, $output, $returnVar);

    if ($returnVar === 0) {
        $ready = true;
        break;
    }
    $attempt++;
    sleep(2);
    echo '.';
}

if (!$ready) {
    echo "\nError: MySQL did not become ready in time.\n";
    exit(1);
}
echo "\nMySQL is ready!\n";

// 7. Artisan key:generate
runCommand("$SAIL artisan key:generate", 'Generating App Key');

// 8. Artisan storage:link
runCommand("$SAIL artisan storage:link", 'Linking Storage');

// 9. Artisan migrate
runCommand("$SAIL artisan migrate --force", 'Running Database Migrations');

// 10. Artisan test
runCommand("$SAIL test", 'Running Tests');

echo "\n🚀 Setup complete! Your application is running via Sail.\n";
echo "🔗 Access your app at: " . (getenv('APP_URL') ?: 'http://localhost:20143') . "\n";
echo "💡 Use 'make down' to stop the environment.\n";
