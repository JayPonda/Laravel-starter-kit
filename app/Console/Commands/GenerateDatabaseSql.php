<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class GenerateDatabaseSql extends Command
{
    protected $signature = 'db:generate-sql';

    protected $description = 'Generate SQL file to create database, user and grant privileges';

    public function handle(): int
    {
        $connection = config('database.default');
        
        if ($connection !== 'mysql' && $connection !== 'mariadb') {
            $this->error('This command only supports MySQL/MariaDB connections.');
            $this->info('Current connection: ' . $connection);
            return Command::FAILURE;
        }

        $dbName = config("database.connections.{$connection}.database");
        $dbUser = config("database.connections.{$connection}.username");
        $dbPassword = config("database.connections.{$connection}.password");
        $dbHost = config("database.connections.{$connection}.host");

        if (! $dbName || ! $dbUser) {
            $this->error('Please configure DB_DATABASE and DB_USERNAME in .env');
            return Command::FAILURE;
        }

        $sql = <<<SQL
-- Create database if not exists
CREATE DATABASE IF NOT EXISTS `{$dbName}`;
-- Create user and grant all privileges
CREATE USER IF NOT EXISTS '{$dbUser}'@'%' IDENTIFIED BY '{$dbPassword}';
GRANT ALL PRIVILEGES ON `{$dbName}`.* TO '{$dbUser}'@'%';
FLUSH PRIVILEGES;
SQL;

        $path = base_path('storage/database.sql');
        File::put($path, $sql);

        $this->info("SQL file generated at: storage/database.sql");
        $this->info("Database: {$dbName}");
        $this->info("User: {$dbUser}");
        $this->info("Host: {$dbHost}");

        return Command::SUCCESS;
    }
}
