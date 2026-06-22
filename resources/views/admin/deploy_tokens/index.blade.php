@extends('layouts.admin')
@section('title', 'Deploy Tokens')

@section('content')
    @include('admin.partials.flash')
    <div class="rd-breadcrumb">Management / Deploy Tokens</div>

    @if ($newToken)
        <div class="rd-card" style="margin-bottom:18px;">
            <div class="rd-card__header">
                <h3 class="rd-card__title">New deploy token</h3>
            </div>
            <div class="rd-card__body">
                <span class="rd-help" style="display:block;margin-bottom:8px;">Copy this token now — it will not be shown again.</span>
                <input class="rd-input" type="text" value="{{ $newToken }}" readonly onclick="this.select()" style="font-family:monospace;">
            </div>
        </div>
    @endif

    <div class="rd-card" style="margin-bottom:18px;max-width:560px;">
        <div class="rd-card__header">
            <h3 class="rd-card__title">Create token</h3>
        </div>
        <div class="rd-card__body">
            @if ($errors->any())
                <div class="rd-toast rd-toast--error" style="margin-bottom:16px;">
                    <i class="ri-error-warning-line"></i><span>{{ $errors->first() }}</span>
                </div>
            @endif
            <form method="POST" action="{{ route('admin.deploy-tokens.store') }}">
                @csrf
                <div class="rd-field">
                    <label class="rd-label" for="name">Name</label>
                    <input class="rd-input" id="name" name="name" value="{{ old('name') }}" placeholder="e.g. Office rollout">
                </div>
                <div class="rd-field">
                    <label class="rd-label" for="expires_at">Expires at</label>
                    <input class="rd-input" id="expires_at" name="expires_at" type="date" value="{{ old('expires_at') }}">
                    <span class="rd-help">Leave empty for a token that never expires.</span>
                </div>
                <div class="rd-row">
                    <button type="submit" class="rd-btn rd-btn--primary"><i class="ri-add-line"></i> Create token</button>
                </div>
            </form>
        </div>
    </div>

    <div class="rd-card">
        <div class="rd-card__header">
            <h3 class="rd-card__title">Your deploy tokens</h3>
            <a href="{{ route('admin.devices.pending') }}" class="rd-btn rd-btn--ghost"><i class="ri-shield-check-line"></i> Pending Devices</a>
        </div>
        <div class="rd-card__body" style="padding:0;">
            <table class="rd-table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Created</th>
                        <th>Expires</th>
                        <th>Last used</th>
                        <th style="text-align:right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                @forelse ($tokens as $token)
                    <tr>
                        <td style="color:var(--rd-text-bright);font-weight:600;">{{ $token->name ?: '—' }}</td>
                        <td class="rd-muted">{{ $token->created_at?->format('Y-m-d H:i') ?? '—' }}</td>
                        <td class="rd-muted">{{ $token->expires_at?->format('Y-m-d') ?? 'Never' }}</td>
                        <td class="rd-muted">{{ $token->last_used_at?->diffForHumans() ?? '—' }}</td>
                        <td style="text-align:right;">
                            <div class="rd-row" style="justify-content:flex-end;">
                                <form method="POST" action="{{ route('admin.deploy-tokens.destroy', $token) }}" class="m-0">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="rd-btn rd-btn--danger" data-confirm="Revoke this deploy token?"><i class="ri-delete-bin-line"></i> Revoke</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="rd-muted" style="text-align:center;padding:28px;">No deploy tokens yet.</td></tr>
                @endforelse
                </tbody>
            </table>
            @include('admin.partials.pagination', ['paginator' => $tokens])
        </div>
    </div>
@endsection
