@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<div class="page-header">
    <h1>Welcome back, {{ explode(' ', Auth::user()->name)[0] }}!</h1>
</div>

<div class="grid">
    <!-- User Profile Card -->
    <div class="card profile-info">
        <div class="avatar">
            {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
        </div>
        <h2>User Profile</h2>
        <div class="info-item">
            <span class="info-label">Name</span>
            <span class="info-value">{{ Auth::user()->name }}</span>
        </div>
        <div class="info-item">
            <span class="info-label">Email</span>
            <span class="info-value">{{ Auth::user()->email }}</span>
        </div>
        <div class="info-item">
            <span class="info-label">Member Since</span>
            <span class="info-value">{{ Auth::user()->created_at->format('M d, Y') }}</span>
        </div>
        <div class="info-item">
            <span class="info-label">Role</span>
            <span class="info-value">Developer</span>
        </div>
    </div>

    <!-- Main Content Area -->
    <div style="display: flex; flex-direction: column; gap: 20px;">
        <div class="card welcome-card">
            <h2>Getting Started</h2>
            <p>Your application is ready! This dashboard is built with a clean Service-Oriented Architecture and is ready for your custom business logic.</p>
            
            <div class="stats-grid">
                <div class="stat-item">
                    <span class="stat-value">MySQL 8</span>
                    <span class="stat-label">Connected</span>
                </div>
                <div class="stat-item">
                    <span class="stat-value">Redis 7</span>
                    <span class="stat-label">Connected</span>
                </div>
                <div class="stat-item">
                    <span class="stat-value">Sanctum</span>
                    <span class="stat-label">Enabled</span>
                </div>
            </div>
        </div>

        <div class="card">
            <h2>Next Steps</h2>
            <div class="action-list">
                <a href="/auth.html" class="action-item">
                    <div class="action-icon">API</div>
                    <div>
                        <strong>Try Standalone API Client</strong>
                        <div style="font-size: 12px; color: #8d949e;">Test your Sanctum endpoints instantly.</div>
                    </div>
                </a>
                <a href="https://laravel.com/docs" target="_blank" class="action-item">
                    <div class="action-icon">DOC</div>
                    <div>
                        <strong>Explore Laravel Docs</strong>
                        <div style="font-size: 12px; color: #8d949e;">Learn more about the world's best framework.</div>
                    </div>
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
