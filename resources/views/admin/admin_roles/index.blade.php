@extends('layouts.admin')
@section('title', 'Admin Roles')

@php
    $typeLabels = [
        \App\Models\AdminRole::TYPE_GLOBAL => 'Global',
        \App\Models\AdminRole::TYPE_INDIVIDUAL => 'Individual',
        \App\Models\AdminRole::TYPE_GROUP => 'Group-scoped',
    ];
@endphp

@section('content')
    @include('admin.partials.flash')
    <div class="rd-breadcrumb">System / Admin Roles</div>

    <div class="rd-card">
        <div class="rd-card__header">
            <h3 class="rd-card__title">Admin Roles</h3>
            <a href="{{ route('admin.roles.create') }}" class="rd-btn rd-btn--primary"><i class="ri-add-line"></i> New role</a>
        </div>
        <div class="rd-card__body" style="padding:0;">
            <table class="rd-table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Type</th>
                        <th>Permissions</th>
                        <th>Members</th>
                        <th style="text-align:right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                @forelse ($roles as $role)
                    <tr>
                        <td style="color:var(--rd-text-bright);font-weight:600;">{{ $role->name }}</td>
                        <td><span class="rd-badge rd-badge--muted">{{ $typeLabels[$role->type] ?? 'Unknown' }}</span></td>
                        <td class="rd-muted">
                            @if ($role->type === \App\Models\AdminRole::TYPE_GLOBAL)
                                Full access
                            @else
                                {{ count((array) $role->perms) }} granted
                            @endif
                        </td>
                        <td class="rd-muted">{{ $role->users_count }}</td>
                        <td style="text-align:right;">
                            <div class="rd-row" style="justify-content:flex-end;">
                                <a href="{{ route('admin.roles.edit', $role) }}" class="rd-btn rd-btn--ghost"><i class="ri-pencil-line"></i> Edit</a>
                                <form method="POST" action="{{ route('admin.roles.destroy', $role) }}" class="m-0">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="rd-btn rd-btn--danger" data-confirm="Delete role '{{ $role->name }}'? Members lose these permissions."><i class="ri-delete-bin-line"></i></button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="rd-muted" style="text-align:center;padding:28px;">No admin roles yet.</td></tr>
                @endforelse
                </tbody>
            </table>
            @include('admin.partials.pagination', ['paginator' => $roles])
        </div>
    </div>
@endsection
