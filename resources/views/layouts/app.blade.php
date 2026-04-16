<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Dashboard') - {{ config('app.name') }}</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #f0f2f5; color: #1c1e21; }
        
        .navbar { background: #fff; border-bottom: 1px solid #ddd; padding: 0 20px; height: 60px; display: flex; align-items: center; justify-content: space-between; position: sticky; top: 0; z-index: 100; }
        .navbar-brand { font-size: 20px; font-weight: 700; color: #0066cc; text-decoration: none; }
        .user-nav { display: flex; align-items: center; gap: 15px; }
        .user-name { font-weight: 500; font-size: 14px; }
        .logout-btn { background: transparent; border: 1px solid #ddd; padding: 6px 12px; border-radius: 4px; cursor: pointer; font-size: 13px; }
        .logout-btn:hover { background: #f5f5f5; }

        .main-container { max-width: 1000px; margin: 30px auto; padding: 0 20px; }
        .page-header { margin-bottom: 24px; }
        .page-header h1 { font-size: 28px; }

        .grid { display: grid; grid-template-columns: 1fr 2fr; gap: 20px; }
        @media (max-width: 768px) { .grid { grid-template-columns: 1fr; } }

        .card { background: #fff; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); padding: 24px; height: 100%; }
        .card h2 { font-size: 18px; margin-bottom: 16px; color: #4b4f56; }

        .profile-info { text-align: center; }
        .avatar { width: 80px; height: 80px; background: #0066cc; color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 32px; font-weight: bold; margin: 0 auto 15px; }
        .info-item { display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid #f0f2f5; font-size: 14px; }
        .info-item:last-child { border-bottom: none; }
        .info-label { color: #8d949e; }
        .info-value { font-weight: 500; }

        .welcome-card { background: linear-gradient(135deg, #0066cc 0%, #004a99 100%); color: white; border: none; }
        .welcome-card h2 { color: white; }
        .welcome-card p { opacity: 0.9; line-height: 1.5; margin-bottom: 20px; }

        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: 15px; }
        .stat-item { background: rgba(255,255,255,0.1); padding: 15px; border-radius: 6px; text-align: center; }
        .stat-value { font-size: 20px; font-weight: bold; display: block; }
        .stat-label { font-size: 12px; opacity: 0.8; }

        .action-list { list-style: none; }
        .action-item { padding: 12px; border-radius: 6px; border: 1px solid #eee; margin-bottom: 10px; display: flex; align-items: center; gap: 10px; text-decoration: none; color: inherit; transition: background 0.2s; }
        .action-item:hover { background: #f9f9f9; border-color: #ddd; }
        .action-icon { width: 30px; height: 30px; background: #e7f3ff; color: #0066cc; border-radius: 6px; display: flex; align-items: center; justify-content: center; font-weight: bold; }
    </style>
</head>
<body>
    <nav class="navbar">
        <a href="/" class="navbar-brand">Laravel Boilerplate</a>
        <div class="user-nav">
            <span class="user-name">{{ Auth::user()->name }}</span>
            <form action="{{ route('logout') }}" method="POST" style="display: inline;">
                @csrf
                <button type="submit" class="logout-btn">Logout</button>
            </form>
        </div>
    </nav>

    <div class="main-container">
        @yield('content')
    </div>
</body>
</html>
