@extends('layouts.admin')
@section('title', 'LDAP / Active Directory')

@section('content')
    @include('admin.partials.flash')
    <div class="rd-breadcrumb">Access / LDAP</div>

    <div class="rd-card">
        <div class="rd-card__header">
            <h3 class="rd-card__title">LDAP / Active Directory</h3>
            <div class="rd-row">
                @if ($enabled)
                    <span class="rd-badge rd-badge--online"><span class="dot"></span> Enabled</span>
                @else
                    <span class="rd-badge rd-badge--offline"><span class="dot"></span> Disabled</span>
                @endif
                <form method="POST" action="{{ route('admin.ldap.test') }}" class="m-0">
                    @csrf
                    <button type="submit" class="rd-btn rd-btn--primary" @disabled(! $enabled)>
                        <i class="ri-plug-line"></i> Test connection
                    </button>
                </form>
            </div>
        </div>
        <div class="rd-card__body">
            @unless ($extensionLoaded)
                <p class="rd-help" style="color:var(--rd-danger);">
                    The PHP <code>ldap</code> extension is not loaded — LDAP authentication will not work.
                </p>
            @endunless

            <p class="rd-help" style="margin-bottom:18px;">
                These settings are read-only and configured via environment variables
                (<code>LDAP_*</code> in <code>config/ldap.php</code>). LDAP is disabled by default;
                when enabled, client and admin login try LDAP first and fall back to local passwords.
            </p>

            <table class="rd-table">
                <tbody>
                    <tr>
                        <td class="rd-muted" style="width:240px;">Status</td>
                        <td style="color:var(--rd-text-bright);">{{ $enabled ? 'Enabled' : 'Disabled' }}</td>
                    </tr>
                    <tr>
                        <td class="rd-muted">Host</td>
                        <td>{{ $host !== '' ? $host : '—' }}</td>
                    </tr>
                    <tr>
                        <td class="rd-muted">Port</td>
                        <td>{{ $port }}</td>
                    </tr>
                    <tr>
                        <td class="rd-muted">Base DN</td>
                        <td>{{ $baseDn !== '' ? $baseDn : '—' }}</td>
                    </tr>
                    <tr>
                        <td class="rd-muted">Bind DN (service account)</td>
                        <td>{{ $bindDn !== '' ? $bindDn : '(anonymous)' }}</td>
                    </tr>
                    <tr>
                        <td class="rd-muted">Bind password</td>
                        <td>{{ $bindPasswordSet ? '•••••••• (set)' : '(not set)' }}</td>
                    </tr>
                    <tr>
                        <td class="rd-muted">User filter</td>
                        <td><code>{{ $userFilter !== '' ? $userFilter : '—' }}</code></td>
                    </tr>
                    <tr>
                        <td class="rd-muted">Username attribute</td>
                        <td>{{ $usernameAttr !== '' ? $usernameAttr : '—' }}</td>
                    </tr>
                    <tr>
                        <td class="rd-muted">Email attribute</td>
                        <td>{{ $emailAttr !== '' ? $emailAttr : '—' }}</td>
                    </tr>
                    <tr>
                        <td class="rd-muted">Display-name attribute</td>
                        <td>{{ $displayNameAttr !== '' ? $displayNameAttr : '—' }}</td>
                    </tr>
                    <tr>
                        <td class="rd-muted">StartTLS</td>
                        <td>{{ $useStartTls ? 'On' : 'Off' }}</td>
                    </tr>
                    <tr>
                        <td class="rd-muted">TLS certificate verification</td>
                        <td>{{ $tlsVerify ? 'On' : 'Off' }}</td>
                    </tr>
                    <tr>
                        <td class="rd-muted">Admin group</td>
                        <td>{{ $adminGroup !== '' ? $adminGroup : '(none)' }}</td>
                    </tr>
                    <tr>
                        <td class="rd-muted">Allow group</td>
                        <td>{{ $allowGroup !== '' ? $allowGroup : '(any)' }}</td>
                    </tr>
                    <tr>
                        <td class="rd-muted">Sync on login</td>
                        <td>{{ $sync ? 'On' : 'Off' }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
@endsection

@if (session('error'))
    @push('scripts')
        <script>$(function () { RD.toast(@json(session('error')), 'error'); });</script>
    @endpush
@endif
