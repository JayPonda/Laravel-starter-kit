<?php

// Applies a frontend preset by copying the chosen variant into the project
// root and removing the other variant's files. All operations are whole-file
// (copy / delete) — no in-file content surgery happens here.

require_once __DIR__.'/utility.php';

$longOpts = ['mode:', 'dry-run'];
$options = getopt('', $longOpts);

$mode = $options['mode'] ?? null;
$dryRun = isset($options['dry-run']);

if (! in_array($mode, ['blade', 'public'], true)) {
    fwrite(STDERR, "Usage: php setup/apply-frontend.php --mode=blade|public [--dry-run]\n");
    exit(1);
}

$root = realpath(__DIR__.'/..');
$preset = $root.'/presets/'.$mode;

if (! is_dir($preset)) {
    fwrite(STDERR, "Preset directory not found: {$preset}\n");
    exit(1);
}

// Files that belong to EITHER variant. Clearing them to a neutral state before
// copying guarantees no leftover files from the other mode remain.
$resetFiles = [
    // public-page owned
    'public/index.html',
    'public/dashboard.html',
    'public/login.html',
    'public/register.html',
    'public/myfiles.html',
    'public/edit.html',
    'public/constants.js',
    'public/styleguild-public.css',
    'public/app.css',
    'docker-compose.override.yml',
    // NOTE: public/content.json is SHARED (used by blade dashboard + public pages)
    // so it is never deleted.
    // blade owned
    'routes/web.php',
    'resources/views/auth',
    'resources/views/dashboard.blade.php',
    'resources/views/layouts',
    'resources/views/files',
    'resources/views/healthcheck.blade.php',
    'app/Http/Controllers/WebAuthController.php',
    'resources/css/app.css',
    'resources/js/app.js',
    'resources/js/bootstrap.js',
    'vite.config.js',
    'tests/Feature/Http/Controllers/WebAuthControllerTest.php',
];

function removePath(string $path, bool $dryRun): void
{
    if (! file_exists($path) && ! is_link($path)) {
        return;
    }
    if ($dryRun) {
        echo "[dry-run] DELETE  {$path}\n";
        return;
    }
    if (is_dir($path) && ! is_link($path)) {
        $items = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($items as $item) {
            if ($item->isDir()) {
                rmdir($item->getRealPath());
            } else {
                unlink($item->getRealPath());
            }
        }
        rmdir($path);
    } else {
        unlink($path);
    }
    echo "DELETED  {$path}\n";
}

function copyPreset(string $from, string $to, bool $dryRun): void
{
    $rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($from, FilesystemIterator::SKIP_DOTS));
    foreach ($rii as $file) {
        if (! $file->isFile()) {
            continue;
        }
        $target = $to.'/'.substr($file->getRealPath(), strlen($from) + 1);
        if ($dryRun) {
            echo "[dry-run] COPY     {$file->getRealPath()} -> {$target}\n";
            continue;
        }
        if (! is_dir(dirname($target))) {
            mkdir(dirname($target), 0777, true);
        }
        copy($file->getRealPath(), $target);
        echo "COPIED   {$target}\n";
    }
}

/**
 * Set or update a key in a .env file (creates the file if missing).
 */
function setEnvVar(string $path, string $key, string $value, bool $dryRun): void
{
    if ($dryRun) {
        echo "[dry-run] ENV      {$key}={$value} in {$path}\n";
        return;
    }
    $lines = file_exists($path) ? file($path, FILE_IGNORE_NEW_LINES) : [];
    $found = false;
    foreach ($lines as &$line) {
        if (str_starts_with($line, $key.'=') || str_starts_with($line, '# '.$key.'=')) {
            $line = "{$key}={$value}";
            $found = true;
        }
    }
    unset($line);
    if (! $found) {
        $lines[] = "{$key}={$value}";
    }
    file_put_contents($path, implode("\n", $lines)."\n");
    echo "ENV      {$key}={$value}\n";
}


echo "==========================================\n";
echo " Frontend mode : {$mode}\n";
echo " Dry run      : ".($dryRun ? 'YES (no changes written)' : 'NO')."\n";
echo "==========================================\n\n";

echo ">>> Resetting frontend surfaces (removing the unused variant)...\n";
foreach ($resetFiles as $rel) {
    removePath($root.'/'.$rel, $dryRun);
}

echo "\n>>> Applying '{$mode}' preset...\n";
copyPreset($preset, $root, $dryRun);

echo "\n✅ Frontend set to '{$mode}'.\n";
if ($dryRun) {
    echo "(Dry run — no files were changed. Re-run without --dry-run to apply.)\n";
}
