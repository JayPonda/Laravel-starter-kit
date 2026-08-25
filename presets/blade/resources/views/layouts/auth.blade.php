<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Laravel Auth')</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #f5f5f5; min-height: 100vh; display: flex; align-items: center; justify-content: center; }
        .container { width: 100%; max-width: 400px; padding: 20px; }
        .card { background: white; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); padding: 24px; }
        h1 { font-size: 24px; margin-bottom: 20px; text-align: center; }
        .form-group { margin-bottom: 16px; }
        label { display: block; margin-bottom: 4px; font-weight: 500; }
        input { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px; }
        button { width: 100%; padding: 12px; background: #0066cc; color: white; border: none; border-radius: 4px; font-size: 14px; font-weight: 500; cursor: pointer; }
        .error { color: #c00; font-size: 12px; margin-top: 4px; }
        .alert { padding: 10px; border-radius: 4px; margin-bottom: 16px; font-size: 14px; }
        .alert-error { background: #fee; color: #c00; }
        .nav-links { margin-top: 16px; text-align: center; font-size: 14px; }
        .nav-links a { color: #0066cc; text-decoration: none; }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            @yield('content')
        </div>
    </div>
</body>
</html>
