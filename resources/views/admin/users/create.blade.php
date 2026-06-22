@extends('layouts.admin')
@section('title', 'New User')

@section('content')
    <div class="rd-breadcrumb">Management / Users / New</div>

    <div class="rd-card" style="max-width:640px;">
        <div class="rd-card__header">
            <h3 class="rd-card__title">New user</h3>
            <a href="{{ route('admin.users.index') }}" class="rd-btn rd-btn--ghost"><i class="ri-arrow-left-line"></i> Back</a>
        </div>
        <div class="rd-card__body">
            @if ($errors->any())
                <div class="rd-toast rd-toast--error" style="margin-bottom:16px;">
                    <i class="ri-error-warning-line"></i><span>{{ $errors->first() }}</span>
                </div>
            @endif

            <form method="POST" action="{{ route('admin.users.store') }}">
                @csrf
                <div class="rd-field">
                    <label class="rd-label" for="username">Username</label>
                    <input class="rd-input" id="username" name="username" value="{{ old('username') }}" required>
                </div>
                <div class="rd-field">
                    <label class="rd-label" for="password">Password</label>
                    <input class="rd-input" id="password" name="password" type="password" autocomplete="new-password" required>
                    <span class="rd-help">At least 6 characters.</span>
                </div>
                <div class="rd-field">
                    <label class="rd-label" for="email">Email</label>
                    <input class="rd-input" id="email" name="email" type="email" value="{{ old('email') }}">
                </div>
                <div class="rd-field">
                    <label class="rd-label" for="display_name">Display name</label>
                    <input class="rd-input" id="display_name" name="display_name" value="{{ old('display_name') }}">
                </div>
                <div class="rd-field">
                    <label class="rd-label" for="group_id">Group</label>
                    <select class="rd-select" id="group_id" name="group_id">
                        <option value="">— None —</option>
                        @foreach ($groups as $g)
                            <option value="{{ $g->id }}" @selected(old('group_id') == $g->id)>{{ $g->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="rd-field">
                    <label class="rd-label" for="is_admin">Role</label>
                    <select class="rd-select" id="is_admin" name="is_admin">
                        <option value="0" @selected(! old('is_admin'))>User</option>
                        <option value="1" @selected(old('is_admin'))>Administrator</option>
                    </select>
                </div>
                <div class="rd-field">
                    <label class="rd-label" for="status">Status</label>
                    <select class="rd-select" id="status" name="status">
                        <option value="{{ \App\Models\User::STATUS_NORMAL }}" @selected(old('status', \App\Models\User::STATUS_NORMAL) == \App\Models\User::STATUS_NORMAL)>Active</option>
                        <option value="{{ \App\Models\User::STATUS_DISABLED }}" @selected(old('status') === (string) \App\Models\User::STATUS_DISABLED)>Disabled</option>
                        <option value="{{ \App\Models\User::STATUS_UNVERIFIED }}" @selected(old('status') === (string) \App\Models\User::STATUS_UNVERIFIED)>Unverified</option>
                    </select>
                </div>
                <div class="rd-field">
                    <label class="rd-label" for="login_verify">Login verification</label>
                    <select class="rd-select" id="login_verify" name="login_verify">
                        <option value="off" @selected(old('login_verify', 'off') === 'off')>Off</option>
                        <option value="email" @selected(old('login_verify') === 'email')>Email code</option>
                        <option value="totp" @selected(old('login_verify') === 'totp')>TOTP</option>
                    </select>
                </div>
                <div class="rd-field">
                    <label class="rd-label" for="note">Note</label>
                    <input class="rd-input" id="note" name="note" value="{{ old('note') }}">
                </div>
                <div class="rd-row" style="margin-top:8px;">
                    <button type="submit" class="rd-btn rd-btn--primary"><i class="ri-save-line"></i> Create user</button>
                </div>
            </form>
        </div>
    </div>
@endsection
