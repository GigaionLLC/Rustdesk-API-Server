@extends('layouts.admin')
@section('title', 'Login Logs')

@section('content')
    <div class="rd-breadcrumb">Audit / Login Logs</div>

    <div class="rd-card">
        <div class="rd-card__header">
            <h3 class="rd-card__title">Login Logs</h3>
            <form method="GET" action="{{ route('admin.audit.logins') }}" class="rd-row">
                <input class="rd-input" type="search" name="q" value="{{ $q }}" placeholder="Search client / device / ip" style="width:240px;">
                <button class="rd-btn rd-btn--ghost" type="submit"><i class="ri-search-line"></i></button>
            </form>
        </div>
        <div class="rd-card__body" style="padding:0;">
            <table class="rd-table">
                <thead>
                    <tr>
                        <th>Time</th>
                        <th>User</th>
                        <th>Type</th>
                        <th>Client</th>
                        <th>Device</th>
                        <th>Platform</th>
                        <th>IP</th>
                    </tr>
                </thead>
                <tbody>
                @forelse ($logs as $log)
                    <tr>
                        <td class="rd-muted">{{ $log->created_at?->format('Y-m-d H:i:s') ?? '—' }}</td>
                        <td style="color:var(--rd-text-bright);">{{ $log->user->username ?? '—' }}</td>
                        <td><span class="rd-badge rd-badge--muted">{{ $log->type }}</span></td>
                        <td class="rd-muted">{{ $log->client ?: '—' }}</td>
                        <td class="rd-muted">{{ $log->device_id ?: '—' }}</td>
                        <td class="rd-muted">{{ $log->platform ?: '—' }}</td>
                        <td class="rd-muted">{{ $log->ip ?: '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="rd-muted" style="text-align:center;padding:28px;">No login logs.</td></tr>
                @endforelse
                </tbody>
            </table>
            @include('admin.partials.pagination', ['paginator' => $logs])
        </div>
    </div>
@endsection
