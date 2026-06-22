@extends('layouts.admin')
@section('title', 'Edit User')

@section('content')
    <div class="rd-breadcrumb">Management / Users / Edit</div>

    <div class="rd-grid rd-grid--2" style="align-items:start;">
        <div class="rd-card">
            <div class="rd-card__header">
                <h3 class="rd-card__title">{{ $user->username }}</h3>
                <a href="{{ route('admin.users.index') }}" class="rd-btn rd-btn--ghost"><i class="ri-arrow-left-line"></i> Back</a>
            </div>
            <div class="rd-card__body">
                <form class="rd-liveform" data-url="{{ route('admin.users.update', $user) }}" data-method="PUT">
                    <div class="rd-field">
                        <label class="rd-label">Username</label>
                        <input class="rd-input" value="{{ $user->username }}" disabled>
                        <span class="rd-help">Username cannot be changed.</span>
                    </div>
                    <div class="rd-field">
                        <label class="rd-label" for="email">Email</label>
                        <input class="rd-input" id="email" name="email" type="email" value="{{ $user->email }}">
                    </div>
                    <div class="rd-field">
                        <label class="rd-label" for="display_name">Display name</label>
                        <input class="rd-input" id="display_name" name="display_name" value="{{ $user->display_name }}">
                    </div>
                    <div class="rd-field">
                        <label class="rd-label" for="group_id">Group</label>
                        <select class="rd-select" id="group_id" name="group_id">
                            <option value="">— None —</option>
                            @foreach ($groups as $g)
                                <option value="{{ $g->id }}" @selected($user->group_id == $g->id)>{{ $g->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="rd-field">
                        <label class="rd-label" for="is_admin">Role</label>
                        <select class="rd-select" id="is_admin" name="is_admin">
                            <option value="0" @selected(! $user->is_admin)>User</option>
                            <option value="1" @selected($user->is_admin)>Administrator — Full access (global)</option>
                        </select>
                        <span class="rd-help">Full access (global) overrides any scoped admin roles below.</span>
                    </div>
                    <div class="rd-field">
                        <label class="rd-label" for="admin_roles">Admin roles</label>
                        <select class="rd-select" id="admin_roles" multiple size="5" data-roles-multiselect data-target="#admin_role_ids" @disabled($adminRoles->isEmpty())>
                            @foreach ($adminRoles as $r)
                                <option value="{{ $r->id }}" @selected(in_array((int) $r->id, $assignedRoleIds, true))>{{ $r->name }}</option>
                            @endforeach
                        </select>
                        <input type="hidden" id="admin_role_ids" name="admin_role_ids" value="{{ implode(',', $assignedRoleIds) }}">
                        <span class="rd-help">
                            @if ($adminRoles->isEmpty())
                                No admin roles defined yet. Create them under System / Admin Roles.
                            @else
                                Scoped, delegated console permissions. Ignored when Full access (global) is set.
                            @endif
                        </span>
                    </div>
                    <div class="rd-field">
                        <label class="rd-label" for="status">Status</label>
                        <select class="rd-select" id="status" name="status">
                            <option value="{{ \App\Models\User::STATUS_NORMAL }}" @selected($user->status === \App\Models\User::STATUS_NORMAL)>Active</option>
                            <option value="{{ \App\Models\User::STATUS_DISABLED }}" @selected($user->status === \App\Models\User::STATUS_DISABLED)>Disabled</option>
                            <option value="{{ \App\Models\User::STATUS_UNVERIFIED }}" @selected($user->status === \App\Models\User::STATUS_UNVERIFIED)>Unverified</option>
                        </select>
                    </div>
                    <div class="rd-field">
                        <label class="rd-label">Login policy</label>
                        <label class="rd-row" style="gap:8px;align-items:center;">
                            <input type="hidden" name="force_sso" value="0">
                            <input type="checkbox" id="force_sso" name="force_sso" value="1" @checked($user->force_sso)>
                            <span class="rd-muted">Require SSO login (block local password; LDAP/OIDC still allowed)</span>
                        </label>
                    </div>
                    <div class="rd-field">
                        <label class="rd-label" for="login_verify">Login verification</label>
                        <select class="rd-select" id="login_verify" name="login_verify">
                            <option value="off" @selected($user->login_verify === 'off')>Off</option>
                            <option value="email" @selected($user->login_verify === 'email')>Email code</option>
                            <option value="totp" @selected($user->login_verify === 'totp')>TOTP</option>
                        </select>
                    </div>
                    <div class="rd-field">
                        <label class="rd-label" for="note">Note</label>
                        <input class="rd-input" id="note" name="note" value="{{ $user->note }}">
                    </div>
                    <div class="rd-row" style="margin-top:8px;">
                        <button type="submit" class="rd-btn rd-btn--primary rd-btn--save" data-state="idle">Save</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="rd-card">
            <div class="rd-card__header">
                <h3 class="rd-card__title">Reset password</h3>
            </div>
            <div class="rd-card__body">
                <form class="rd-liveform" data-url="{{ route('admin.users.password', $user) }}" data-method="PUT">
                    <div class="rd-field">
                        <label class="rd-label" for="password">New password</label>
                        <input class="rd-input" id="password" name="password" type="password" autocomplete="new-password" placeholder="••••••••">
                        <span class="rd-help">At least 6 characters. The user must use the new password on next login.</span>
                    </div>
                    <div class="rd-row" style="margin-top:8px;">
                        <button type="submit" class="rd-btn rd-btn--primary rd-btn--save" data-state="idle">Reset password</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    $(function () {
        // Mirror the admin-roles multi-select into a hidden CSV field so the live-save form
        // (which flattens array inputs) submits the full set, matching the groups editor.
        $('select[data-roles-multiselect]').each(function () {
            var $sel = $(this);
            var $target = $($sel.data('target'));
            $sel.on('change', function () {
                $target.val(($sel.val() || []).join(','));
                $sel.closest('form').trigger('change');
            });
        });
    });
</script>
@endpush
