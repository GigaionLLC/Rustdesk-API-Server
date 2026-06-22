@extends('layouts.admin')
@section('title', 'Pending Devices')

@section('content')
    @include('admin.partials.flash')
    <div class="rd-breadcrumb">Management / Pending Devices</div>

    <div class="rd-card">
        <div class="rd-card__header">
            <h3 class="rd-card__title">Pending Devices</h3>
            <a href="{{ route('admin.deploy-tokens.index') }}" class="rd-btn rd-btn--ghost"><i class="ri-key-2-line"></i> Deploy Tokens</a>
        </div>
        <div class="rd-card__body" style="padding:0;">
            <table class="rd-table">
                <thead>
                    <tr>
                        <th>Device</th>
                        <th>OS</th>
                        <th>Owner</th>
                        <th>Registered</th>
                        <th style="text-align:right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                @forelse ($devices as $device)
                    <tr>
                        <td>
                            <div style="color:var(--rd-text-bright);font-weight:600;">{{ $device->hostname ?: $device->alias ?: $device->rustdesk_id }}</div>
                            <div class="rd-muted" style="font-size:12px;">{{ $device->rustdesk_id }}</div>
                        </td>
                        <td class="rd-muted">{{ $device->os ?: '—' }}</td>
                        <td class="rd-muted">{{ $device->user->username ?? '—' }}</td>
                        <td class="rd-muted">{{ $device->created_at?->diffForHumans() ?? '—' }}</td>
                        <td style="text-align:right;">
                            <div class="rd-row" style="justify-content:flex-end;">
                                <form method="POST" action="{{ route('admin.devices.approve', $device) }}" class="m-0">
                                    @csrf
                                    @method('PUT')
                                    <button type="submit" class="rd-btn rd-btn--primary"><i class="ri-check-line"></i> Approve</button>
                                </form>
                                <form method="POST" action="{{ route('admin.devices.reject', $device) }}" class="m-0">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="rd-btn rd-btn--danger" data-confirm="Reject and delete this device?"><i class="ri-close-line"></i> Reject</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="rd-muted" style="text-align:center;padding:28px;">No devices awaiting approval.</td></tr>
                @endforelse
                </tbody>
            </table>
            @include('admin.partials.pagination', ['paginator' => $devices])
        </div>
    </div>
@endsection
