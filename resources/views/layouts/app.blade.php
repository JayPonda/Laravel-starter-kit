<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Dashboard') - {{ config('app.name') }}</title>
    <link rel="stylesheet" href="/styleguild.css">
</head>
<body>
    <nav class="navbar">
        <a href="/" class="navbar-brand">Laravel Boilerplate</a>
        <div class="user-nav">
            <span class="user-name">{{ Auth::user()->name }}</span>
            <form action="{{ route('logout') }}" method="POST" style="display: inline;">
                @csrf
                <button type="submit" class="logout-link">Logout</button>
            </form>
        </div>
    </nav>

    <div class="main-container">
        @yield('content')
    </div>
</body>
</html>
