@extends('layouts.admin')
@section('title', 'Address Book')

@section('content')
    @include('admin.partials.flash')
    <div class="rd-breadcrumb">Management / Address Books / {{ $addressBook->name ?: 'Default' }}</div>

    <div class="rd-card" style="margin-bottom:20px;">
        <div class="rd-card__header">
            <h3 class="rd-card__title">Tags</h3>
            <a href="{{ route('admin.address-books.index') }}" class="rd-btn rd-btn--ghost"><i class="ri-arrow-left-line"></i> Back</a>
        </div>
        <div class="rd-card__body">
            @forelse ($addressBook->tags as $tag)
                <span class="rd-badge rd-badge--muted" style="margin:0 6px 6px 0;">
                    @if ($tag->color)
                        <span class="dot" style="display:inline-block;width:10px;height:10px;border-radius:50%;margin-right:5px;background:#{{ substr(sprintf('%08X', (int) $tag->color), 2) }};"></span>
                    @endif
                    {{ $tag->name }}
                    <form method="POST" action="{{ route('admin.address-books.tags.destroy', $tag) }}" style="display:inline;margin:0;">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="rd-btn rd-btn--ghost" style="padding:0 4px;border:0;background:none;" data-confirm="Remove tag '{{ $tag->name }}'?" title="Remove tag"><i class="ri-close-line"></i></button>
                    </form>
                </span>
            @empty
                <span class="rd-muted">No tags in this address book.</span>
            @endforelse
        </div>
    </div>

    <div class="rd-card">
        <div class="rd-card__header">
            <h3 class="rd-card__title">Peers</h3>
            <span class="rd-muted" style="font-size:13px;">{{ $peers->total() }} total</span>
        </div>
        <div class="rd-card__body" style="padding:0;">
            <table class="rd-table">
                <thead>
                    <tr>
                        <th>RustDesk ID</th>
                        <th>Alias / Host</th>
                        <th>Platform</th>
                        <th>RDP</th>
                        <th>Tags</th>
                        <th style="text-align:right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                @forelse ($peers as $peer)
                    <tr>
                        <td style="color:var(--rd-text-bright);font-weight:600;">{{ $peer->rustdesk_id }}</td>
                        <td class="rd-muted">{{ $peer->alias ?: $peer->hostname ?: '—' }}</td>
                        <td class="rd-muted">{{ $peer->platform ?: '—' }}</td>
                        <td class="rd-muted">
                            @if ($peer->rdp_port || $peer->rdp_username)
                                {{ $peer->rdp_username ?: '' }}@if ($peer->rdp_port){{ $peer->rdp_username ? '@' : '' }}:{{ $peer->rdp_port }}@endif
                            @else
                                —
                            @endif
                        </td>
                        <td>
                            @forelse (($peer->tags ?? []) as $t)
                                <span class="rd-badge rd-badge--muted" style="margin:0 4px 4px 0;">{{ $t }}</span>
                            @empty
                                <span class="rd-muted">—</span>
                            @endforelse
                        </td>
                        <td style="text-align:right;">
                            <form method="POST" action="{{ route('admin.address-books.peers.destroy', $peer) }}" class="m-0">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="rd-btn rd-btn--danger" data-confirm="Remove peer '{{ $peer->rustdesk_id }}'?"><i class="ri-delete-bin-line"></i></button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="rd-muted" style="text-align:center;padding:28px;">No peers in this address book.</td></tr>
                @endforelse
                </tbody>
            </table>
            @include('admin.partials.pagination', ['paginator' => $peers])
        </div>
    </div>
@endsection
