<?php

require_once __DIR__.'/utility.php';

$env = getAppEnv();

// In Docker context, we usually want to bind to all interfaces
$bind = getEnvVar($env, 'REDIS_BIND', '0.0.0.0');
$port = getEnvVar($env, 'REDIS_PORT', '6379');
$password = getEnvVar($env, 'REDIS_PASSWORD', '');
$maxMemory = getEnvVar($env, 'REDIS_MAX_MEMORY', '256mb');
$dir = getEnvVar($env, 'REDIS_DIR', '/data');
// In Docker, we often log to stdout (empty logfile)
$logFile = getEnvVar($env, 'REDIS_LOGFILE', '');

$aclUser = "user default on ";
if (empty($password) || $password === 'null') {
    $aclUser .= "nopass ";
} else {
    $aclUser .= ">" . $password . " ";
}
$aclUser .= "~* +@all -flushall -flushdb -config -keys -shutdown -debug -module";

$config = <<<CONF
# Redis Configuration File
# Generated for boilerplate application

# Network
bind {$bind}
port {$port}
protected-mode no

# Access Control List (ACL)
# Default user (used by Laravel) can do everything EXCEPT dangerous commands
# Commands restricted: FLUSHALL, FLUSHDB, CONFIG, KEYS, SHUTDOWN, DEBUG, MODULE
{$aclUser}

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
logfile "{$logFile}"

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

echo "✅ Redis config generated at: redis.conf\n";
echo "📍 Host: {$bind}\n";
echo "🔌 Port: {$port}\n";
if (empty($password) || $password === 'null') {
    echo "🔓 Auth: No password (ACL unrestricted commands disabled)\n";
} else {
    echo "🔒 Auth: Password enabled (ACL security active)\n";
}
