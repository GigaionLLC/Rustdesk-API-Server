@extends('layouts.admin')
@section('title', 'Edit OAuth Provider')

@section('content')
    <div class="rd-breadcrumb">Access / OAuth Providers / {{ $provider->op }}</div>

    <div class="rd-card" style="max-width:640px;">
        <div class="rd-card__header">
            <h3 class="rd-card__title">{{ $provider->op }}</h3>
            <a href="{{ route('admin.oauth-providers.index') }}" class="rd-btn rd-btn--ghost"><i class="ri-arrow-left-line"></i> Back</a>
        </div>
        <div class="rd-card__body">
            @if ($errors->any())
                <div class="rd-toast rd-toast--error" style="margin-bottom:16px;">
                    <i class="ri-error-warning-line"></i><span>{{ $errors->first() }}</span>
                </div>
            @endif
            <form method="POST" action="{{ route('admin.oauth-providers.update', $provider) }}">
                @csrf
                @method('PUT')

                <div class="rd-field">
                    <label class="rd-label" for="op">Key (op)</label>
                    <input class="rd-input" id="op" name="op" value="{{ old('op', $provider->op) }}" required>
                    <span class="rd-help">Unique provider key the client requests.</span>
                </div>

                <div class="rd-field">
                    <label class="rd-label" for="type">Type</label>
                    <select class="rd-select" id="type" name="type">
                        @foreach ($types as $type)
                            <option value="{{ $type }}" @selected(old('type', $provider->type) === $type)>{{ $type }}</option>
                        @endforeach
                    </select>
                    <span class="rd-help"><code>oidc</code> requires an issuer.</span>
                </div>

                <div class="rd-field">
                    <label class="rd-label" for="client_id">Client ID</label>
                    <input class="rd-input" id="client_id" name="client_id" value="{{ old('client_id', $provider->client_id) }}" required>
                </div>

                <div class="rd-field">
                    <label class="rd-label" for="client_secret">Client Secret</label>
                    <input class="rd-input" id="client_secret" name="client_secret" type="password" autocomplete="new-password" placeholder="Leave blank to keep current secret">
                    <span class="rd-help">Write-only. Leave blank to keep the stored secret.</span>
                </div>

                <div class="rd-field">
                    <label class="rd-label" for="issuer">Issuer</label>
                    <input class="rd-input" id="issuer" name="issuer" value="{{ old('issuer', $provider->issuer) }}" placeholder="https://accounts.example.com">
                    <span class="rd-help">Required for the <code>oidc</code> type.</span>
                </div>

                <div class="rd-field">
                    <label class="rd-label" for="scopes">Scopes</label>
                    <input class="rd-input" id="scopes" name="scopes" value="{{ old('scopes', $provider->scopes) }}" placeholder="openid,profile,email">
                    <span class="rd-help">Comma-separated. Defaults to <code>openid,profile,email</code> when blank.</span>
                </div>

                <div class="rd-field">
                    <label class="rd-label" for="pkce_method">PKCE method</label>
                    <select class="rd-select" id="pkce_method" name="pkce_method">
                        <option value="S256" @selected(old('pkce_method', $provider->pkce_method) === 'S256')>S256</option>
                        <option value="plain" @selected(old('pkce_method', $provider->pkce_method) === 'plain')>plain</option>
                    </select>
                </div>

                <div class="rd-field">
                    <label class="rd-label">Options</label>
                    <label class="rd-row" style="gap:8px;align-items:center;margin-bottom:6px;">
                        <input type="checkbox" name="auto_register" value="1" @checked(old('auto_register', $provider->auto_register))>
                        <span class="rd-muted">Auto-register new users on first login</span>
                    </label>
                    <label class="rd-row" style="gap:8px;align-items:center;margin-bottom:6px;">
                        <input type="checkbox" name="pkce_enable" value="1" @checked(old('pkce_enable', $provider->pkce_enable))>
                        <span class="rd-muted">Enable PKCE</span>
                    </label>
                    <label class="rd-row" style="gap:8px;align-items:center;">
                        <input type="checkbox" name="enabled" value="1" @checked(old('enabled', $provider->enabled))>
                        <span class="rd-muted">Enabled (offered to clients)</span>
                    </label>
                </div>

                <div class="rd-row" style="margin-top:8px;">
                    <button type="submit" class="rd-btn rd-btn--primary"><i class="ri-save-line"></i> Save provider</button>
                </div>
            </form>
        </div>
    </div>
@endsection
