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

        {{-- Bulk-action bar (shown when ≥1 user is selected) --}}
        <form method="POST" id="bulkForm" action="{{ route('admin.users.bulk') }}" class="rd-bulkbar" style="display:none;">
            @csrf
            <span id="bulkCount" class="rd-muted" style="font-size:13px;"></span>
            <select class="rd-select" id="bulkAction" name="action" style="width:170px;">
                <option value="enable">Enable</option>
                <option value="disable">Disable</option>
                <option value="group">Set group</option>
                <option value="delete">Delete</option>
            </select>
            <select class="rd-select" name="value" id="bulkGroup" style="width:200px;display:none;" disabled>
                <option value="">— No group —</option>
                @foreach ($groups as $g)<option value="{{ $g->id }}">{{ $g->name }}</option>@endforeach
            </select>
            <button type="submit" class="rd-btn rd-btn--primary"><i class="ri-check-line"></i> Apply</button>
            <button type="button" class="rd-btn rd-btn--ghost" id="bulkClear">Clear</button>
            <span id="bulkIds"></span>
        </form>

        <div class="rd-card__body" style="padding:0;">
            <table class="rd-table">
                <thead>
                    <tr>
                        <th style="width:34px;"><input type="checkbox" id="checkAll" title="Select all on this page"></th>
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
                        <td><input type="checkbox" class="usr-check" value="{{ $user->id }}"></td>
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
                    <tr><td colspan="7" class="rd-muted" style="text-align:center;padding:28px;">No users found.</td></tr>
                @endforelse
                </tbody>
            </table>
            @include('admin.partials.pagination', ['paginator' => $users])
        </div>
    </div>
@endsection

@push('scripts')
<script>
    $(function () {
        function selectedIds() {
            return $('.usr-check:checked').map(function () { return this.value; }).get();
        }
        function refreshBulk() {
            var n = selectedIds().length;
            $('#bulkCount').text(n + ' selected');
            $('#bulkForm').toggle(n > 0);
        }
        $('#checkAll').on('change', function () {
            $('.usr-check').prop('checked', this.checked);
            refreshBulk();
        });
        $(document).on('change', '.usr-check', function () {
            var all = $('.usr-check'), checked = $('.usr-check:checked');
            $('#checkAll').prop('checked', all.length > 0 && checked.length === all.length);
            refreshBulk();
        });
        $('#bulkClear').on('click', function () {
            $('.usr-check, #checkAll').prop('checked', false);
            refreshBulk();
        });

        // The group select only applies to the "Set group" action.
        function syncAction() {
            var isGroup = $('#bulkAction').val() === 'group';
            $('#bulkGroup').toggle(isGroup).prop('disabled', !isGroup);
        }
        $('#bulkAction').on('change', syncAction);
        syncAction();

        $('#bulkForm').on('submit', function (e) {
            var ids = selectedIds(), action = $('#bulkAction').val();
            if (!ids.length) { e.preventDefault(); return; }
            var verb = { enable: 'enable', disable: 'disable', delete: 'DELETE', group: 'update' }[action] || 'update';
            if (!window.confirm(verb + ' ' + ids.length + ' user(s)?')) { e.preventDefault(); return; }
            var $box = $('#bulkIds').empty();
            ids.forEach(function (id) { $('<input type="hidden" name="ids[]">').val(id).appendTo($box); });
        });
    });
</script>
@endpush
