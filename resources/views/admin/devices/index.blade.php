@extends('layouts.admin')
@section('title', 'Devices')

@section('content')
    @include('admin.partials.flash')
    <div class="rd-breadcrumb">Management / Devices</div>

    <div class="rd-card">
        <div class="rd-card__header">
            <h3 class="rd-card__title">Devices</h3>
            <form method="GET" action="{{ route('admin.devices.index') }}" class="rd-row">
                <input class="rd-input" type="search" name="q" value="{{ $q }}" placeholder="Search id / host / alias" style="width:220px;">
                <select class="rd-select" name="status" style="width:130px;" onchange="this.form.submit()">
                    <option value="">All status</option>
                    <option value="online"  @selected($status === 'online')>Online</option>
                    <option value="offline" @selected($status === 'offline')>Offline</option>
                </select>
                <button class="rd-btn rd-btn--ghost" type="submit"><i class="ri-search-line"></i> Search</button>
            </form>
        </div>
        <div class="rd-card__body" style="padding:0;">
            <table class="rd-table">
                <thead>
                    <tr>
                        <th>Device</th>
                        <th>OS</th>
                        <th>Owner</th>
                        <th>Status</th>
                        <th>Last seen</th>
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
                        <td>
                            <span class="rd-badge rd-badge--{{ $device->is_online ? 'online' : 'offline' }}">
                                <span class="dot"></span>{{ $device->is_online ? 'Online' : 'Offline' }}
                            </span>
                        </td>
                        <td class="rd-muted">{{ $device->last_online_at?->diffForHumans() ?? '—' }}</td>
                        <td style="text-align:right;">
                            <div class="rd-row" style="justify-content:flex-end;">
                                <a href="{{ route('admin.devices.edit', $device) }}" class="rd-btn rd-btn--ghost"><i class="ri-pencil-line"></i> Edit</a>
                                <form method="POST" action="{{ route('admin.devices.destroy', $device) }}" class="m-0">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="rd-btn rd-btn--danger" data-confirm="Delete this device? This cannot be undone."><i class="ri-delete-bin-line"></i></button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="rd-muted" style="text-align:center;padding:28px;">No devices found.</td></tr>
                @endforelse
                </tbody>
            </table>
            @include('admin.partials.pagination', ['paginator' => $devices])
        </div>
    </div>
@endsection
