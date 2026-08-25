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
$options = getopt('i', ['frontend:']);
$shouldInstall = isset($options['i']);

// Resolve frontend mode: --frontend=blade|public (default: blade)
$frontendMode = $options['frontend'] ?? null;
if ($frontendMode === null) {
    // Interactive prompt (skipped when not a TTY -> default to blade)
    if (stream_isatty(STDIN)) {
        echo "\n>>> Choose the frontend rendering mode:\n";
        echo "   1) blade   (server-rendered Laravel views + session auth) [default]\n";
        echo "   2) public  (standalone static HTML pages talking to the REST API)\n";
        echo "Select [1/2] (Enter = blade): ";
        $answer = trim(fgets(STDIN));
        $frontendMode = ($answer === '2') ? 'public' : 'blade';
    } else {
        $frontendMode = 'blade';
    }
}
if (! in_array($frontendMode, ['blade', 'public'], true)) {
    echo "Error: invalid --frontend value '{$frontendMode}'. Use 'blade' or 'public'.\n";
    exit(1);
}
echo "\n>>> Frontend mode: {$frontendMode}\n";

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

// 3b. Apply the chosen frontend preset (must run before `sail up` so the
//     correct docker-compose.override.yml is in place for the public mode).
runCommand("php setup/apply-frontend.php --mode={$frontendMode}", "Applying '{$frontendMode}' frontend preset");

// 4. Generate MySQL config (Docker volumes depend on this)
runCommand('php setup/generate-db-sql.php', 'Generating MySQL Config');

// 5. Start Sail
runCommand("$SAIL up -d", 'Starting Laravel Sail Containers');

// 6. Wait for MySQL to be ready
echo "\n>>> Waiting for MySQL to be ready...\n";
$maxAttempts = 30;
$attempt = 0;
$ready = false;

while ($attempt < $maxAttempts) {
    // Get MySQL container name dynamically
    exec("docker compose ps -q mysql 2>/dev/null", $output, $returnVar);
    $containerId = trim($output[0] ?? '');

    if ($containerId) {
        // Check container health status
        exec("docker inspect --format='{{.State.Health.Status}}' $containerId 2>/dev/null", $healthOutput, $healthReturn);
        $healthStatus = trim($healthOutput[0] ?? '');

        if ($healthStatus === 'healthy') {
            $ready = true;
            break;
        }
    }

    $attempt++;
    sleep(2);
    echo '.';
    $output = [];
    $healthOutput = [];
}

if (!$ready) {
    // Simple fallback - container is up
    if ($containerId) {
        echo "\nMySQL container is running, proceeding...\n";
        $ready = true;
    } else {
        echo "Error: MySQL container not found.\n";
        exit(1);
    }
}
echo "\nMySQL is ready!\n";

// 7. Setup MinIO bucket
echo "\n>>> Setting up MinIO bucket...\n";

// Get bucket name from .env or use default 'laravel'
$envFile = file_get_contents('.env');
preg_match('/AWS_BUCKET=(.*)/', $envFile, $matches);
$bucketName = !empty(trim($matches[1] ?? '')) ? trim($matches[1]) : 'laravel';

// Set alias (ignore if already exists)
passthru("docker compose exec -T minio mc alias set local http://minio:9000 sail password > /dev/null 2>&1");

// Check if bucket exists, create if not
$checkBucket = "docker compose exec -T minio mc ls local/{$bucketName} > /dev/null 2>&1";
exec($checkBucket, $output, $returnVar);

if ($returnVar !== 0) {
    // Bucket doesn't exist - create it
    $createBucket = "docker compose exec -T minio mc mb local/{$bucketName}";
    exec($createBucket, $output, $returnVar);
    if ($returnVar === 0) {
        echo "Created bucket '{$bucketName}'\n";
    } else {
        echo "Warning: Could not create bucket '{$bucketName}'\n";
    }
} else {
    echo "Bucket '{$bucketName}' already exists\n";
}

// Set anonymous download policy (optional - allows direct URL access to files)
passthru("docker compose exec -T minio mc anonymous set download local/{$bucketName} > /dev/null 2>&1 || true");
echo "Bucket '{$bucketName}' is ready!\n";

// 8. Artisan key:generate
// Use docker compose exec directly to specify the correct service name
runCommand("docker compose exec -T backend php artisan key:generate", 'Generating App Key');

// 9. Fix storage permissions
echo "\n>>> Fixing storage permissions...\n";
passthru("docker compose exec -T backend chmod -R 777 /var/www/html/storage/logs /var/www/html/storage/framework/cache /var/www/html/storage/framework/sessions /var/www/html/storage/framework/views /var/www/html/storage/framework/cache/temp 2>/dev/null || true");
passthru("docker compose exec -T backend chown -R sail:sail /var/www/html/storage/logs /var/www/html/storage/framework 2>/dev/null || true");
echo "Storage permissions fixed!\n";

// 10. Artisan storage:link
runCommand("docker compose exec -T backend php artisan storage:link", 'Linking Storage');

// 11. Artisan migrate
runCommand("docker compose exec -T backend php artisan migrate --force", 'Running Database Migrations');

// 12. Artisan test
runCommand("docker compose exec -T backend php artisan test", 'Running Tests');

echo "\n🚀 Setup complete! Your application is running via Sail.\n";
echo "🔗 Access your app at: http://localhost:12354\n";
echo "📦 MinIO bucket: {$bucketName}\n";
echo "💡 Use 'make down' to stop the environment.\n";