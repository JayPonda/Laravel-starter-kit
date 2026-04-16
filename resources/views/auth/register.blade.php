@extends('layouts.auth')
@section('title', 'Register')
@section('content')
    <h1>Register</h1>

    <form method="POST" action="{{ route('register') }}">
        @csrf
        <div class="form-group">
            <label>Name</label>
            <input type="text" name="name" value="{{ old('name') }}" required autofocus>
            @error('name') <div class="error">{{ $message }}</div> @enderror
        </div>
        <div class="form-group">
            <label>Email</label>
            <input type="email" name="email" value="{{ old('email') }}" required>
            @error('email') <div class="error">{{ $message }}</div> @enderror
        </div>
        <div class="form-group">
            <label>Password</label>
            <input type="password" name="password" required>
            @error('password') <div class="error">{{ $message }}</div> @enderror
        </div>
        <div class="form-group">
            <label>Confirm Password</label>
            <input type="password" name="password_confirmation" required>
        </div>
        <button type="submit">Register</button>
    </form>

    <div class="nav-links">
        Already have an account? <a href="{{ route('login') }}">Login</a>
    </div>
@endsection
