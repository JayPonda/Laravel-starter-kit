@php
    $content = json_decode(file_get_contents(public_path('content.json')), true);
@endphp

@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<div class="page-header">
    <h1>Welcome back, {{ explode(' ', Auth::user()->name)[0] }}!</h1>
</div>

<div class="grid">
    <!-- User Profile Card -->
    <div class="card text-center">
        <div class="avatar">
            {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
        </div>
        <h2>{{ $content['profile']['title'] }}</h2>
        <div class="info-item">
            <span class="info-label">{{ $content['profile']['labels']['name'] }}</span>
            <span class="info-value">{{ Auth::user()->name }}</span>
        </div>
        <div class="info-item">
            <span class="info-label">{{ $content['profile']['labels']['email'] }}</span>
            <span class="info-value">{{ Auth::user()->email }}</span>
        </div>
        <div class="info-item">
            <span class="info-label">{{ $content['profile']['labels']['member_since'] }}</span>
            <span class="info-value">{{ Auth::user()->created_at->format('M d, Y') }}</span>
        </div>
        <div class="info-item">
            <span class="info-label">{{ $content['profile']['role_label'] }}</span>
            <span class="info-value">{{ $content['profile']['default_role'] }}</span>
        </div>
    </div>

    <!-- Main Content Area -->
    <div class="flex-column">
        <div class="card welcome-card">
            <h2>{{ $content['dashboard']['welcome']['title'] }}</h2>
            <p>{{ $content['dashboard']['welcome']['description'] }}</p>
            
            <div class="stats-grid">
                <!-- Stat 1 -->
                <div class="stat-item">
                    <span class="stat-value">{{ $content['dashboard']['stats'][0]['value'] }}</span>
                    <span class="stat-label">{{ $content['dashboard']['stats'][0]['label'] }}</span>
                </div>
                <!-- Stat 2 -->
                <div class="stat-item">
                    <span class="stat-value">{{ $content['dashboard']['stats'][1]['value'] }}</span>
                    <span class="stat-label">{{ $content['dashboard']['stats'][1]['label'] }}</span>
                </div>
                <!-- Stat 3 -->
                <div class="stat-item">
                    <span class="stat-value">{{ $content['dashboard']['stats'][2]['value'] }}</span>
                    <span class="stat-label">{{ $content['dashboard']['stats'][2]['label'] }}</span>
                </div>
            </div>
        </div>

        <div class="card">
            <h2 id="links-title">Quick Links</h2>
            <div class="action-list">
                @foreach ($content['dashboard']['quick_links'] as $link)
                    <a href="{{ $link['url'] }}" class="action-item" {{ (!empty($link['external']) || str_starts_with($link['url'], 'http')) ? 'target="_blank"' : '' }}>
                        <div class="action-icon">{{ $link['icon'] }}</div>
                        <div>
                            <strong>{{ $link['title'] }}</strong>
                            <p class="text-small">{{ $link['description'] }}</p>
                        </div>
                    </a>
                @endforeach
            </div>
        </div>
    </div>
</div>
@endsection
