@extends('layouts.admin')
@section('title', 'Groups')

@php
    $typeLabels = [
        \App\Models\Group::TYPE_DEFAULT => 'Default',
        \App\Models\Group::TYPE_SHARED => 'Shared',
    ];
@endphp

@section('content')
    @include('admin.partials.flash')
    <div class="rd-breadcrumb">Management / Groups</div>

    <div class="rd-card">
        <div class="rd-card__header">
            <h3 class="rd-card__title">User Groups</h3>
            <div class="rd-row">
                <a href="{{ route('admin.device-groups.index') }}" class="rd-btn rd-btn--ghost"><i class="ri-device-line"></i> Device Groups</a>
                <a href="{{ route('admin.groups.create') }}" class="rd-btn rd-btn--primary"><i class="ri-add-line"></i> New group</a>
            </div>
        </div>
        <div class="rd-card__body" style="padding:0;">
            <table class="rd-table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Type</th>
                        <th>Members</th>
                        <th>Note</th>
                        <th style="text-align:right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                @forelse ($groups as $group)
                    <tr>
                        <td style="color:var(--rd-text-bright);font-weight:600;">{{ $group->name }}</td>
                        <td><span class="rd-badge rd-badge--muted">{{ $typeLabels[$group->type] ?? 'Unknown' }}</span></td>
                        <td class="rd-muted">{{ $memberCounts[$group->id] ?? 0 }}</td>
                        <td class="rd-muted">{{ $group->note ?: '—' }}</td>
                        <td style="text-align:right;">
                            <div class="rd-row" style="justify-content:flex-end;">
                                <a href="{{ route('admin.groups.edit', $group) }}" class="rd-btn rd-btn--ghost"><i class="ri-pencil-line"></i> Edit</a>
                                <form method="POST" action="{{ route('admin.groups.destroy', $group) }}" class="m-0">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="rd-btn rd-btn--danger" data-confirm="Delete group '{{ $group->name }}'?"><i class="ri-delete-bin-line"></i></button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="rd-muted" style="text-align:center;padding:28px;">No groups yet.</td></tr>
                @endforelse
                </tbody>
            </table>
            @include('admin.partials.pagination', ['paginator' => $groups])
        </div>
    </div>
@endsection
