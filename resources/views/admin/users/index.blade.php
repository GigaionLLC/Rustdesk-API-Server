@extends('layouts.admin')
@section('title', 'Users')

@php
    $statusLabels = [
        \App\Models\User::STATUS_NORMAL => ['Active', 'online'],
        \App\Models\User::STATUS_DISABLED => ['Disabled', 'offline'],
        \App\Models\User::STATUS_UNVERIFIED => ['Unverified', 'muted'],
    ];
@endphp

@section('content')
    @include('admin.partials.flash')
    <div class="rd-breadcrumb">Management / Users</div>

    <div class="rd-card">
        <div class="rd-card__header">
            <h3 class="rd-card__title">Users</h3>
            <div class="rd-row">
                <form method="GET" action="{{ route('admin.users.index') }}" class="rd-row">
                    <input class="rd-input" type="search" name="q" value="{{ $q }}" placeholder="Search users" style="width:220px;">
                    <button class="rd-btn rd-btn--ghost" type="submit"><i class="ri-search-line"></i></button>
                </form>
                <a href="{{ route('admin.users.create') }}" class="rd-btn rd-btn--primary"><i class="ri-add-line"></i> New user</a>
            </div>
        </div>
        <div class="rd-card__body" style="padding:0;">
            <table class="rd-table">
                <thead>
                    <tr>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Display name</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th style="text-align:right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                @forelse ($users as $user)
                    @php($s = $statusLabels[$user->status] ?? ['Unknown', 'muted'])
                    <tr>
                        <td style="color:var(--rd-text-bright);font-weight:600;">{{ $user->username }}</td>
                        <td class="rd-muted">{{ $user->email ?: '—' }}</td>
                        <td class="rd-muted">{{ $user->display_name ?: '—' }}</td>
                        <td>
                            @if ($user->is_admin)
                                <span class="rd-badge rd-badge--online"><span class="dot"></span>Admin</span>
                            @else
                                <span class="rd-badge rd-badge--muted">User</span>
                            @endif
                        </td>
                        <td><span class="rd-badge rd-badge--{{ $s[1] }}"><span class="dot"></span>{{ $s[0] }}</span></td>
                        <td style="text-align:right;">
                            <div class="rd-row" style="justify-content:flex-end;">
                                <a href="{{ route('admin.users.edit', $user) }}" class="rd-btn rd-btn--ghost"><i class="ri-pencil-line"></i> Edit</a>
                                <form method="POST" action="{{ route('admin.users.destroy', $user) }}" class="m-0">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="rd-btn rd-btn--danger" data-confirm="Delete user '{{ $user->username }}'? This cannot be undone."><i class="ri-delete-bin-line"></i></button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="rd-muted" style="text-align:center;padding:28px;">No users found.</td></tr>
                @endforelse
                </tbody>
            </table>
            @include('admin.partials.pagination', ['paginator' => $users])
        </div>
    </div>
@endsection
