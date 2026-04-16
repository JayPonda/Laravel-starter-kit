<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class GenerateRedisConfig extends Command
{
    protected $signature = 'redis:generate-config';

    protected $description = 'Generate Redis configuration file from environment variables';

    public function handle(): int
    {
        $bind = env('REDIS_HOST', '127.0.0.1');
        $port = env('REDIS_PORT', '6379');
        $password = env('REDIS_PASSWORD', '');
        $maxMemory = env('REDIS_MAX_MEMORY', '256mb');
        $dir = env('REDIS_DIR', '/data');
        $logFile = env('REDIS_LOGFILE', '/var/log/redis/redis.log');
        $requirePass = $password ? $password : 'change_this_password';

        $config = <<<CONF
# Redis Configuration File
# Generated for boilerplate application

# Network
bind {$bind}
port {$port}
protected-mode yes

# Security
requirepass {$requirePass}

# Rename dangerous commands
rename-command FLUSHALL ""
rename-command FLUSHDB ""
rename-command CONFIG ""
rename-command KEYS ""
rename-command SHUTDOWN ""
rename-command DEBUG ""
rename-command MODULE ""

# Memory
maxmemory {$maxMemory}
maxmemory-policy allkeys-lru

# Persistence - Backup to disk
save 900 1
save 300 10
save 60 10000

appendonly yes
appendfsync everysec
dir {$dir}

# Logging
loglevel notice
logfile {$logFile}

# Client
timeout 300
tcp-keepalive 300
maxclients 10000

# Slow log
slowlog-log-slower-than 10000
slowlog-max-len 128
CONF;

        $path = base_path('storage/redis.conf');
        File::put($path, $config);

        $this->info('Redis config generated at: storage/redis.conf');
        $this->info("Host: {$bind}");
        $this->info("Port: {$port}");

        if (empty($password)) {
            $this->warn('WARNING: No REDIS_PASSWORD set in .env - using default. Please set a strong password!');
        }

        return Command::SUCCESS;
    }
}
