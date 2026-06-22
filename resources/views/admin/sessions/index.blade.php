@extends('layouts.admin')
@section('title', 'Live Sessions')

@php
    $connTypes = [0 => 'Remote Desktop', 1 => 'File Transfer', 2 => 'Port Transfer', 3 => 'View Camera', 4 => 'Terminal'];
@endphp

@section('content')
    <div class="rd-breadcrumb">Control / Live Sessions</div>
    @include('admin.partials.flash')

    <div class="rd-card">
        <div class="rd-card__header">
            <h3 class="rd-card__title">Active connections</h3>
            <span class="rd-muted">{{ $sessions->total() }} open</span>
        </div>
        <div class="rd-card__body" style="padding:0;">
            <table class="rd-table">
                <thead>
                    <tr><th>Device</th><th>Controller</th><th>IP</th><th>Type</th><th>Started</th><th style="text-align:right;">Action</th></tr>
                </thead>
                <tbody>
                @forelse ($sessions as $s)
                    <tr>
                        <td>{{ $hostnames[$s->peer_id] ?? $s->peer_id }}</td>
                        <td>{{ $s->from_name ?: ($s->from_peer ?: '—') }}</td>
                        <td class="rd-muted">{{ $s->ip ?: '—' }}</td>
                        <td>{{ $connTypes[$s->type] ?? ('Type '.$s->type) }}</td>
                        <td class="rd-muted">{{ $s->created_at?->diffForHumans() ?? '—' }}</td>
                        <td style="text-align:right;">
                            @if (auth()->user()->hasPermission('sessions.edit'))
                                <form method="POST" action="{{ route('admin.sessions.disconnect') }}" class="m-0" style="display:inline;">
                                    @csrf
                                    <input type="hidden" name="peer_id" value="{{ $s->peer_id }}">
                                    <input type="hidden" name="conn_id" value="{{ $s->conn_id }}">
                                    <button type="submit" class="rd-btn rd-btn--danger"
                                            data-confirm="Force-disconnect this session? It will drop on the device's next heartbeat.">
                                        <i class="ri-shut-down-line"></i> Disconnect
                                    </button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="rd-muted" style="text-align:center;padding:28px;">
                        No active sessions. Open connections appear here (from the audit stream).
                    </td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div style="margin-top:16px;">{{ $sessions->links('admin.partials.pagination') }}</div>
@endsection
