<?php

require_once __DIR__.'/utility.php';

$env = getAppEnv();

$dbName = getEnvVar($env, 'DB_DATABASE', 'laravel');
$dbUser = getEnvVar($env, 'DB_USERNAME', 'laravel');
$dbPassword = getEnvVar($env, 'DB_PASSWORD', 'password');

$sql = <<<SQL
-- Create database if not exists
CREATE DATABASE IF NOT EXISTS `{$dbName}`;
-- Create user and grant all privileges
CREATE USER IF NOT EXISTS '{$dbUser}'@'%' IDENTIFIED BY '{$dbPassword}';
GRANT ALL PRIVILEGES ON `{$dbName}`.* TO '{$dbUser}'@'%';
FLUSH PRIVILEGES;
SQL;

$path = __DIR__.'/../storage/database.sql';
file_put_contents($path, $sql);

echo "SQL file generated at: storage/database.sql\n";
echo "Database: {$dbName}\n";
echo "User: {$dbUser}\n";
