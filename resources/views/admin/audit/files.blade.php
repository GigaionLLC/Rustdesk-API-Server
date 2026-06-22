@extends('layouts.admin')
@section('title', 'File Transfers')

@section('content')
    <div class="rd-breadcrumb">Audit / File Transfers</div>

    <div class="rd-card">
        <div class="rd-card__header">
            <h3 class="rd-card__title">File Transfer Logs</h3>
            <form method="GET" action="{{ route('admin.audit.files') }}" class="rd-row">
                <input class="rd-input" type="search" name="q" value="{{ $q }}" placeholder="Search peer / path / ip" style="width:240px;">
                <button class="rd-btn rd-btn--ghost" type="submit"><i class="ri-search-line"></i></button>
            </form>
        </div>
        <div class="rd-card__body" style="padding:0;">
            <table class="rd-table">
                <thead>
                    <tr>
                        <th>Time</th>
                        <th>Kind</th>
                        <th>Peer</th>
                        <th>From</th>
                        <th>Path</th>
                        <th>Files</th>
                        <th>IP</th>
                    </tr>
                </thead>
                <tbody>
                @forelse ($logs as $log)
                    <tr>
                        <td class="rd-muted">{{ $log->created_at?->format('Y-m-d H:i:s') ?? '—' }}</td>
                        <td><span class="rd-badge rd-badge--muted">{{ $log->is_file ? 'File' : 'Dir' }}</span></td>
                        <td style="color:var(--rd-text-bright);">{{ $log->peer_id }}</td>
                        <td class="rd-muted">{{ $log->from_name ?: $log->from_peer ?: '—' }}</td>
                        <td class="rd-muted" style="max-width:280px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="{{ $log->path }}">{{ $log->path ?: '—' }}</td>
                        <td class="rd-muted">{{ $log->num }}</td>
                        <td class="rd-muted">{{ $log->ip ?: '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="rd-muted" style="text-align:center;padding:28px;">No file transfer logs.</td></tr>
                @endforelse
                </tbody>
            </table>
            @include('admin.partials.pagination', ['paginator' => $logs])
        </div>
    </div>
@endsection
