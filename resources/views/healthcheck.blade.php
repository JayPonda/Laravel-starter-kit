<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Health Check</title>
    <link rel="stylesheet" href="/styleguild.css">
</head>
<body>
    <div class="auth-container">
        <div class="card card-centered">
            <div class="status-badge {{ $status === 'up' ? 'status-up' : 'status-down' }}">
                System {{ strtoupper($status) }}
            </div>
            <h1>Health Check</h1>
            <p>Current system status as of {{ $timestamp }}</p>

            <div style="text-align: left; margin-top: 20px;">
                <div class="info-item">
                    <span class="info-label">Application</span>
                    <span class="info-value status-up" style="background:none; padding:0;">Online</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Database</span>
                    <span class="info-value {{ $database ? 'status-up' : 'status-down' }}" style="background:none; padding:0;">
                        {{ $database ? 'Connected' : 'Disconnected' }}
                    </span>
                </div>
                <div class="info-item">
                    <span class="info-label">Redis</span>
                    <span class="info-value {{ $redis ? 'status-up' : 'status-down' }}" style="background:none; padding:0;">
                        {{ $redis ? 'Connected' : 'Disconnected' }}
                    </span>
                </div>
            </div>

            <div class="divider">
                <div style="display: flex; gap: 20px; justify-content: center;">
                    <a href="{{ route('login') }}" class="link">Login</a>
                    <a href="{{ route('register') }}" class="link">Register</a>
                    <a href="{{ route('dashboard') }}" class="link">Dashboard</a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
