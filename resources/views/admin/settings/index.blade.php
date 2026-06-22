@extends('layouts.admin')
@section('title', 'Settings')

@php
    // Build a plain {key: value} map in PHP (never inline arrays inside @json()).
    $settingsMap = [];
    foreach ($settings as $row) {
        $settingsMap[$row->key] = $row->value;
    }
@endphp

@section('content')
    <div class="rd-breadcrumb">System / Settings</div>

    <div class="rd-grid rd-grid--2" style="align-items:start;">
        {{-- Generic system settings (key/value) --}}
        <div class="rd-card">
            <div class="rd-card__header">
                <h3 class="rd-card__title">System Settings</h3>
            </div>
            <div class="rd-card__body">
                <form class="rd-liveform" id="settingsForm" data-url="{{ route('admin.settings.update') }}" data-method="PUT">
                    <label class="rd-label">Key / value pairs</label>
                    <div id="settingRows"></div>
                    <button type="button" class="rd-btn rd-btn--ghost" id="addSetting" style="margin:6px 0 14px;"><i class="ri-add-line"></i> Add setting</button>
                    <div class="rd-row">
                        <button type="submit" class="rd-btn rd-btn--primary rd-btn--save" data-state="idle">Save</button>
                    </div>
                    <span class="rd-help" style="margin-top:8px;display:block;">Removing a row and saving deletes that setting.</span>
                </form>
            </div>
        </div>

        {{-- SMTP settings --}}
        <div class="rd-card">
            <div class="rd-card__header">
                <h3 class="rd-card__title">SMTP / Email</h3>
            </div>
            <div class="rd-card__body">
                <form class="rd-liveform" data-url="{{ route('admin.settings.smtp') }}" data-method="PUT">
                    <div class="rd-field">
                        <label class="rd-label" for="host">Host</label>
                        <input class="rd-input" id="host" name="host" value="{{ $smtp['smtp.host'] }}" placeholder="smtp.example.com">
                    </div>
                    <div class="rd-field">
                        <label class="rd-label" for="port">Port</label>
                        <input class="rd-input" id="port" name="port" type="number" value="{{ $smtp['smtp.port'] }}" placeholder="587">
                    </div>
                    <div class="rd-field">
                        <label class="rd-label" for="username">Username</label>
                        <input class="rd-input" id="username" name="username" value="{{ $smtp['smtp.username'] }}" autocomplete="off">
                    </div>
                    <div class="rd-field">
                        <label class="rd-label" for="password">Password</label>
                        <input class="rd-input" id="password" name="password" type="password" autocomplete="new-password" placeholder="{{ $smtp['smtp.password'] !== '' ? '•••••••• (unchanged)' : '' }}">
                        <span class="rd-help">Leave blank to keep the existing password.</span>
                    </div>
                    <div class="rd-field">
                        <label class="rd-label" for="from">From address</label>
                        <input class="rd-input" id="from" name="from" type="email" value="{{ $smtp['smtp.from'] }}" placeholder="noreply@example.com">
                    </div>
                    <div class="rd-field">
                        <label class="rd-label" for="encryption">Encryption</label>
                        <select class="rd-select" id="encryption" name="encryption">
                            <option value=""     @selected($smtp['smtp.encryption'] === '')>Default</option>
                            <option value="tls"  @selected($smtp['smtp.encryption'] === 'tls')>TLS</option>
                            <option value="ssl"  @selected($smtp['smtp.encryption'] === 'ssl')>SSL</option>
                            <option value="none" @selected($smtp['smtp.encryption'] === 'none')>None</option>
                        </select>
                    </div>
                    <div class="rd-row" style="margin-top:8px;">
                        <button type="submit" class="rd-btn rd-btn--primary rd-btn--save" data-state="idle">Save SMTP</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    $(function () {
        var settings = @json($settingsMap, JSON_UNESCAPED_SLASHES);

        function settingRow(key, value) {
            var $row = $(
                '<div class="rd-row" style="margin-bottom:8px;">' +
                '<input class="rd-input" name="setting_keys[]" placeholder="key" style="flex:1;">' +
                '<input class="rd-input" name="setting_values[]" placeholder="value" style="flex:1;">' +
                '<button type="button" class="rd-btn rd-btn--ghost rd-set-remove" title="Remove"><i class="ri-close-line"></i></button>' +
                '</div>'
            );
            $row.find('input[name="setting_keys[]"]').val(key || '');
            $row.find('input[name="setting_values[]"]').val(value == null ? '' : String(value));
            return $row;
        }

        var $rows = $('#settingRows');
        var keys = Object.keys(settings);
        if (keys.length === 0) {
            $rows.append(settingRow('', ''));
        } else {
            keys.forEach(function (k) { $rows.append(settingRow(k, settings[k])); });
        }

        $('#addSetting').on('click', function () {
            $rows.append(settingRow('', ''));
            $('#settingsForm').trigger('change');
        });

        $rows.on('click', '.rd-set-remove', function () {
            $(this).closest('.rd-row').remove();
            $('#settingsForm').trigger('change');
        });
    });
</script>
@endpush
