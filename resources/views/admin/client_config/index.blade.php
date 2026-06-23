@extends('layouts.admin')
@section('title', 'Client Config')

@section('content')
    <div class="rd-breadcrumb">Management / Client Config</div>

    <style>
        .rd-cc { display:grid; grid-template-columns:1fr 300px; gap:18px; align-items:start; }
        .rd-cc__qr { background:#fff; border-radius:10px; padding:14px; display:flex; flex-direction:column; align-items:center; gap:8px; }
        .rd-cc__qr svg { width:240px; height:240px; }
        .rd-out { display:flex; gap:8px; align-items:flex-start; }
        .rd-out textarea { width:100%; font-family:monospace; font-size:12px; resize:vertical; min-height:62px;
            background:var(--rd-surface-2); color:var(--rd-text); border:1px solid var(--rd-border); border-radius:8px; padding:9px 11px; }
        @media (max-width: 900px) { .rd-cc { grid-template-columns:1fr; } }
    </style>

    <div class="rd-card" style="margin-bottom:18px;">
        <div class="rd-card__header"><h3 class="rd-card__title"><i class="ri-qr-code-line"></i> Client Config generator</h3></div>
        <div class="rd-card__body">
            <p class="rd-help" style="margin-top:0;">
                Pre-configure RustDesk clients so users don't enter server details by hand. Fill in your
                servers, then share the QR (mobile), the config string (desktop → <em>Import Server Config</em>),
                the command line, or rename the installer.
            </p>
            <p class="rd-help" style="margin-top:0;">
                Fields are pre-filled from this server's config (<code>RUSTDESK_ID_SERVER</code>,
                <code>RUSTDESK_RELAY_SERVER</code>, <code>RUSTDESK_KEY</code> / <code>RUSTDESK_KEY_FILE</code>,
                <code>RUSTDESK_API_SERVER</code>). Override any of them below.
            </p>
            <form method="GET" action="{{ route('admin.client-config.index') }}">
                <div class="rd-grid rd-grid--2" style="align-items:start;">
                    <div class="rd-field">
                        <label class="rd-label" for="host">ID / Rendezvous server (host)</label>
                        <input class="rd-input" id="host" name="host" value="{{ $host }}" placeholder="rustdesk.example.com" required>
                    </div>
                    <div class="rd-field">
                        <label class="rd-label" for="key">Public key</label>
                        <input class="rd-input" id="key" name="key" value="{{ $key }}" placeholder="hbbs key (…=)">
                    </div>
                    <div class="rd-field">
                        <label class="rd-label" for="relay">Relay server</label>
                        <input class="rd-input" id="relay" name="relay" value="{{ $relay }}" placeholder="rustdesk.example.com (optional)">
                    </div>
                    <div class="rd-field">
                        <label class="rd-label" for="api">API server</label>
                        <input class="rd-input" id="api" name="api" value="{{ $api }}" placeholder="https://api.example.com">
                    </div>
                    <div class="rd-field">
                        <label class="rd-label" for="unlock_pin">Default unlock PIN <span class="rd-muted">(optional)</span></label>
                        <input class="rd-input" id="unlock_pin" name="unlock_pin" value="{{ $unlockPin }}" placeholder="e.g. 1234" inputmode="numeric">
                        <span class="rd-help">Protects the client's local settings. Set at install time via CLI (it can't be pushed by a strategy).</span>
                    </div>
                    <div class="rd-field">
                        <label class="rd-label" for="strategy">Install script from Strategy <span class="rd-muted">(optional)</span></label>
                        <select class="rd-input" id="strategy" name="strategy">
                            <option value="">— None —</option>
                            @foreach ($strategies as $s)
                                <option value="{{ $s->id }}" @selected($strategyId === $s->id)>{{ $s->name }}</option>
                            @endforeach
                        </select>
                        <span class="rd-help">Turns the strategy's options into <code>rustdesk --option …</code> commands for an install script.</span>
                    </div>
                </div>
                <button type="submit" class="rd-btn rd-btn--primary"><i class="ri-magic-line"></i> Generate</button>
            </form>
        </div>
    </div>

    @if ($installScript)
        <div class="rd-card" style="margin-bottom:18px;">
            <div class="rd-card__header"><h3 class="rd-card__title"><i class="ri-terminal-box-line"></i> Install script — “{{ $selectedStrategy->name }}”</h3></div>
            <div class="rd-card__body">
                <p class="rd-help" style="margin-top:0;">
                    Applies this strategy's options at install time via the client CLI (run as
                    <strong>administrator / root</strong> on the installed client). This is the deploy-time
                    equivalent of the heartbeat strategy push — handy for MDM / install scripts.
                    @if ($unlockPin !== '') The default unlock PIN is included as the first line. @endif
                </p>
                @php
                    $scriptCli = ['Linux' => 'ri-ubuntu-fill', 'macOS' => 'ri-apple-fill', 'Windows' => 'ri-windows-fill'];
                @endphp
                @foreach ($installScript as $os => $script)
                    @if ($script !== '')
                        <label class="rd-label" style="margin-top:6px;"><i class="{{ $scriptCli[$os] }}"></i> {{ $os }}</label>
                        <div class="rd-out">
                            <textarea readonly id="scr{{ $os }}" style="min-height:150px;">{{ $script }}</textarea>
                            <button type="button" class="rd-btn rd-btn--ghost rd-copy" data-copy="#scr{{ $os }}"><i class="ri-file-copy-line"></i></button>
                        </div>
                    @endif
                @endforeach
            </div>
        </div>
    @endif

    @if ($unlockPin !== '' && ! $selectedStrategy)
        <div class="rd-card" style="margin-bottom:18px;">
            <div class="rd-card__header"><h3 class="rd-card__title"><i class="ri-lock-password-line"></i> Default unlock PIN (<code>--set-unlock-pin</code>)</h3></div>
            <div class="rd-card__body">
                <p class="rd-help" style="margin-top:0;">
                    The unlock PIN guards the client's own settings. It's stored encrypted per-device, so it
                    <strong>cannot</strong> be pushed by a strategy — run this once on the installed client
                    (<strong>administrator / root</strong> required; fails if the strategy option
                    <code>disable-unlock-pin</code> is set).
                </p>
                @php
                    $pinCli = ['Windows' => 'ri-windows-fill', 'macOS' => 'ri-apple-fill', 'Linux' => 'ri-ubuntu-fill'];
                    $pinCmds = [
                        'Windows' => '"%ProgramFiles%\\RustDesk\\rustdesk.exe" --set-unlock-pin '.$unlockPin,
                        'macOS' => 'sudo /Applications/RustDesk.app/Contents/MacOS/rustdesk --set-unlock-pin '.$unlockPin,
                        'Linux' => 'sudo rustdesk --set-unlock-pin '.$unlockPin,
                    ];
                @endphp
                @foreach ($pinCmds as $os => $cmd)
                    <label class="rd-label" style="margin-top:6px;"><i class="{{ $pinCli[$os] }}"></i> {{ $os }}</label>
                    <div class="rd-out">
                        <textarea readonly id="pin{{ $os }}">{{ $cmd }}</textarea>
                        <button type="button" class="rd-btn rd-btn--ghost rd-copy" data-copy="#pin{{ $os }}"><i class="ri-file-copy-line"></i></button>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    @if ($configString)
        <div class="rd-cc">
            <div>
                <div class="rd-card" style="margin-bottom:18px;">
                    <div class="rd-card__header"><h3 class="rd-card__title">Config string</h3></div>
                    <div class="rd-card__body">
                        <p class="rd-help" style="margin-top:0;">Desktop client → Settings → Network → <strong>ID/Relay server</strong> → <strong>Import Server Config</strong> (paste), or <code>rustdesk --config &lt;string&gt;</code>.</p>
                        <div class="rd-out">
                            <textarea readonly id="cfgString">{{ $configString }}</textarea>
                            <button type="button" class="rd-btn rd-btn--ghost rd-copy" data-copy="#cfgString"><i class="ri-file-copy-line"></i></button>
                        </div>
                    </div>
                </div>

                <div class="rd-card" style="margin-bottom:18px;">
                    <div class="rd-card__header"><h3 class="rd-card__title">Command line (<code>--config</code>)</h3></div>
                    <div class="rd-card__body">
                        <p class="rd-help" style="margin-top:0;">Run once on an already-installed client to apply the server config.</p>
                        @php
                            $cli = [
                                'Windows' => 'ri-windows-fill',
                                'macOS' => 'ri-apple-fill',
                                'Linux' => 'ri-ubuntu-fill',
                            ];
                            $cmds = [
                                'Windows' => '"%ProgramFiles%\\RustDesk\\rustdesk.exe" --config '.$configString,
                                'macOS' => '/Applications/RustDesk.app/Contents/MacOS/rustdesk --config '.$configString,
                                'Linux' => 'rustdesk --config '.$configString,
                            ];
                        @endphp
                        @foreach ($cmds as $os => $cmd)
                            <label class="rd-label" style="margin-top:6px;"><i class="{{ $cli[$os] }}"></i> {{ $os }}</label>
                            <div class="rd-out">
                                <textarea readonly id="cli{{ $os }}">{{ $cmd }}</textarea>
                                <button type="button" class="rd-btn rd-btn--ghost rd-copy" data-copy="#cli{{ $os }}"><i class="ri-file-copy-line"></i></button>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="rd-card">
                    <div class="rd-card__header"><h3 class="rd-card__title">Renamed Windows installer</h3></div>
                    <div class="rd-card__body">
                        <p class="rd-help" style="margin-top:0;">Rename the downloaded <code>rustdesk-*.exe</code> to this filename; on first run it auto-applies the config.</p>
                        <div class="rd-out">
                            <textarea readonly id="cfgInstaller">{{ $installer }}</textarea>
                            <button type="button" class="rd-btn rd-btn--ghost rd-copy" data-copy="#cfgInstaller"><i class="ri-file-copy-line"></i></button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="rd-card">
                <div class="rd-card__header"><h3 class="rd-card__title">Mobile QR</h3></div>
                <div class="rd-card__body">
                    <div class="rd-cc__qr">{!! $qrSvg !!}</div>
                    <p class="rd-help" style="text-align:center;margin-bottom:0;">Mobile app → <strong>＋</strong> → scan to import the server config.</p>
                </div>
            </div>
        </div>
    @endif
@endsection

@push('scripts')
<script>
    $(function () {
        $('.rd-copy').on('click', function () {
            var text = $($(this).data('copy')).val();
            if (navigator.clipboard) {
                navigator.clipboard.writeText(text).then(function () { RD.toast('Copied', 'success'); });
            } else {
                var el = $($(this).data('copy'))[0]; el.select(); document.execCommand('copy');
                RD.toast('Copied', 'success');
            }
        });
    });
</script>
@endpush
