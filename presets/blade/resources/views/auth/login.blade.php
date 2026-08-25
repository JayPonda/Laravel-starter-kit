@extends('layouts.auth')
@section('title', 'Login')
@section('content')
    <h1>Login</h1>

    @if (session('success'))
        <div class="alert" style="background: #efe; color: #060; border: 1px solid #060; padding: 10px; border-radius: 4px; margin-bottom: 16px;">
            {{ session('success') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-error">
            {{ $errors->first() }}
        </div>
    @endif

    <form method="POST" action="{{ route('login') }}">
        @csrf
        <div class="form-group">
            <label>Email</label>
            <input type="email" name="email" value="{{ old('email') }}" required autofocus>
        </div>
        <div class="form-group">
            <label>Password</label>
            <input type="password" name="password" required>
        </div>
        <div class="form-group">
            <label style="display: inline-flex; align-items: center; font-weight: normal; cursor: pointer;">
                <input type="checkbox" name="remember" style="width: auto; margin-right: 8px;"> Remember Me
            </label>
        </div>
        <button type="submit">Login</button>
    </form>

    <div class="nav-links">
        Don't have an account? <a href="{{ route('register') }}">Register</a>
    </div>
@endsection
