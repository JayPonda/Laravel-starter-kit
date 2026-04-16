<?php

require_once __DIR__.'/utility.php';

$env = getAppEnv();

$bind = getEnvVar($env, 'REDIS_HOST', '127.0.0.1');
$port = getEnvVar($env, 'REDIS_PORT', '6379');
$password = getEnvVar($env, 'REDIS_PASSWORD', '');
$maxMemory = getEnvVar($env, 'REDIS_MAX_MEMORY', '256mb');
$dir = getEnvVar($env, 'REDIS_DIR', '/data');
$logFile = getEnvVar($env, 'REDIS_LOGFILE', $dir.'/redis.log');
$requirePass = getEnvVar($env, 'REDIS_PASSWORD', 'password');

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

$path = __DIR__.'/../redis.conf';
file_put_contents($path, $config);

echo "Redis config generated at: ../redis.conf\n";
echo "Host: {$bind}\n";
echo "Port: {$port}\n";
if (empty($password)) {
    echo "WARNING: No REDIS_PASSWORD set in .env - using default.\n";
}
