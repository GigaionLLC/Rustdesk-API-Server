@extends('layouts.admin')
@section('title', 'Alarms')

@section('content')
    @include('admin.partials.flash')
    <div class="rd-breadcrumb">Audit / Alarms</div>

    <div class="rd-card">
        <div class="rd-card__header">
            <h3 class="rd-card__title">Alarms</h3>
            <form method="GET" action="{{ route('admin.alarms.index') }}" class="rd-row">
                <select class="rd-select" name="type" style="width:200px;" onchange="this.form.submit()">
                    <option value="">All types</option>
                    @foreach ($types as $t)
                        <option value="{{ $t }}" @selected($type === $t)>{{ $t }}</option>
                    @endforeach
                </select>
                <button class="rd-btn rd-btn--ghost" type="submit"><i class="ri-filter-3-line"></i> Filter</button>
            </form>
        </div>
        <div class="rd-card__body" style="padding:0;">
            <table class="rd-table">
                <thead>
                    <tr>
                        <th>Time</th>
                        <th>Device</th>
                        <th>Peer</th>
                        <th>Type</th>
                        <th>Message</th>
                        <th>IP</th>
                        <th>Emailed</th>
                        <th style="text-align:right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                @forelse ($alarms as $alarm)
                    <tr>
                        <td class="rd-muted">{{ $alarm->created_at?->format('Y-m-d H:i:s') ?? '—' }}</td>
                        <td class="rd-muted">{{ $alarm->device?->hostname ?: $alarm->device?->alias ?: $alarm->device?->rustdesk_id ?: '—' }}</td>
                        <td style="color:var(--rd-text-bright);">{{ $alarm->peer_id }}</td>
                        <td><span class="rd-badge rd-badge--muted">{{ $alarm->type }}</span></td>
                        <td class="rd-muted">{{ $alarm->message }}</td>
                        <td class="rd-muted">{{ $alarm->ip ?: '—' }}</td>
                        <td>
                            <span class="rd-badge rd-badge--{{ $alarm->emailed ? 'online' : 'muted' }}">
                                <span class="dot"></span>{{ $alarm->emailed ? 'Yes' : 'No' }}
                            </span>
                        </td>
                        <td style="text-align:right;">
                            <div class="rd-row" style="justify-content:flex-end;">
                                <form method="POST" action="{{ route('admin.alarms.destroy', $alarm) }}" class="m-0">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="rd-btn rd-btn--danger" data-confirm="Delete this alarm?"><i class="ri-delete-bin-line"></i></button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="rd-muted" style="text-align:center;padding:28px;">No alarms.</td></tr>
                @endforelse
                </tbody>
            </table>
            @include('admin.partials.pagination', ['paginator' => $alarms])
        </div>
    </div>
@endsection
