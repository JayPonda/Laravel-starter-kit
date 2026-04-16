<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Health Check</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #f0f2f5; color: #1c1e21; display: flex; align-items: center; justify-content: center; min-height: 100vh; }
        .card { background: white; border-radius: 12px; shadow: 0 4px 12px rgba(0,0,0,0.1); width: 100%; max-width: 500px; padding: 32px; text-align: center; }
        .status-badge { display: inline-block; padding: 8px 16px; border-radius: 20px; font-weight: 600; font-size: 14px; margin-bottom: 20px; }
        .status-up { background: #e6fcf5; color: #0ca678; }
        .status-down { background: #fff5f5; color: #fa5252; }
        h1 { font-size: 24px; margin-bottom: 10px; }
        p { color: #868e96; margin-bottom: 24px; }
        .services { text-align: left; }
        .service-item { display: flex; justify-content: space-between; padding: 12px 0; border-bottom: 1px solid #f1f3f5; }
        .service-item:last-child { border-bottom: none; }
        .service-name { font-weight: 500; }
        .service-status { font-size: 14px; }
        .text-up { color: #0ca678; }
        .text-down { color: #fa5252; }
    </style>
</head>
<body>
    <div class="card">
        <div class="status-badge {{ $status === 'up' ? 'status-up' : 'status-down' }}">
            System {{ strtoupper($status) }}
        </div>
        <h1>Health Check</h1>
        <p>Current system status as of {{ $timestamp }}</p>

        <div class="services">
            <div class="service-item">
                <span class="service-name">Application</span>
                <span class="service-status text-up">Online</span>
            </div>
            <div class="service-item">
                <span class="service-name">Database</span>
                <span class="service-status {{ $database ? 'text-up' : 'text-down' }}">
                    {{ $database ? 'Connected' : 'Disconnected' }}
                </span>
            </div>
            <div class="service-item">
                <span class="service-name">Redis</span>
                <span class="service-status {{ $redis ? 'text-up' : 'text-down' }}">
                    {{ $redis ? 'Connected' : 'Disconnected' }}
                </span>
            </div>
        </div>

        <div style="margin-top: 30px; display: flex; flex-direction: column; gap: 12px; align-items: center;">
            <div style="display: flex; gap: 20px;">
                <a href="{{ route('login') }}" style="color: #0066cc; text-decoration: none; font-size: 14px; font-weight: 500;">Login</a>
                <a href="{{ route('register') }}" style="color: #0066cc; text-decoration: none; font-size: 14px; font-weight: 500;">Register</a>
                <a href="{{ route('dashboard') }}" style="color: #0066cc; text-decoration: none; font-size: 14px; font-weight: 500;">Dashboard</a>
            </div>
        </div>
    </div>
</body>
</html>
