@extends('layouts.admin')
@section('title', 'OAuth Providers')

@section('content')
    @include('admin.partials.flash')
    <div class="rd-breadcrumb">Access / OAuth Providers</div>

    <div class="rd-card">
        <div class="rd-card__header">
            <h3 class="rd-card__title">OAuth / OIDC Providers</h3>
            <a href="{{ route('admin.oauth-providers.create') }}" class="rd-btn rd-btn--primary"><i class="ri-add-line"></i> New provider</a>
        </div>
        <div class="rd-card__body" style="padding:0;">
            <table class="rd-table">
                <thead>
                    <tr>
                        <th>Key (op)</th>
                        <th>Type</th>
                        <th>Client ID</th>
                        <th>Auto-register</th>
                        <th>Status</th>
                        <th style="text-align:right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                @forelse ($providers as $provider)
                    <tr>
                        <td style="color:var(--rd-text-bright);font-weight:600;">{{ $provider->op }}</td>
                        <td><span class="rd-badge rd-badge--muted">{{ $provider->type }}</span></td>
                        <td class="rd-muted">{{ $provider->client_id }}</td>
                        <td class="rd-muted">{{ $provider->auto_register ? 'Yes' : 'No' }}</td>
                        <td>
                            @if ($provider->enabled)
                                <span class="rd-badge rd-badge--online"><span class="dot"></span> Enabled</span>
                            @else
                                <span class="rd-badge rd-badge--offline"><span class="dot"></span> Disabled</span>
                            @endif
                        </td>
                        <td style="text-align:right;">
                            <div class="rd-row" style="justify-content:flex-end;">
                                <a href="{{ route('admin.oauth-providers.edit', $provider) }}" class="rd-btn rd-btn--ghost"><i class="ri-pencil-line"></i> Edit</a>
                                <form method="POST" action="{{ route('admin.oauth-providers.destroy', $provider) }}" class="m-0">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="rd-btn rd-btn--danger" data-confirm="Delete provider '{{ $provider->op }}'?"><i class="ri-delete-bin-line"></i></button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="rd-muted" style="text-align:center;padding:28px;">No OAuth providers yet.</td></tr>
                @endforelse
                </tbody>
            </table>
            @include('admin.partials.pagination', ['paginator' => $providers])
        </div>
    </div>
@endsection
