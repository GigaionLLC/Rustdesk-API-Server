@extends('layouts.admin')
@section('title', 'Edit Strategy')

@php
    use App\Models\StrategyAssignment;

    $current = $strategy->options ?? [];
    $customOptions = $customOptions ?? [];
    $targetLabels = [
        StrategyAssignment::TARGET_DEVICE => 'Device',
        StrategyAssignment::TARGET_USER => 'User',
        StrategyAssignment::TARGET_DEVICE_GROUP => 'Device Group',
    ];
@endphp

@section('content')
    @include('admin.partials.flash')
    <div class="rd-breadcrumb">Control / Strategies / {{ $strategy->name }}</div>

    <style>
        .rd-optgroup { border:1px solid var(--rd-border); border-radius:8px; margin-bottom:10px; background:var(--rd-surface-2); }
        .rd-optgroup > summary { cursor:pointer; padding:11px 14px; font-weight:600; color:var(--rd-text-bright);
            list-style:none; display:flex; align-items:center; gap:8px; }
        .rd-optgroup > summary::-webkit-details-marker { display:none; }
        .rd-optgroup > summary .rd-chev { margin-left:auto; transition:transform .15s; opacity:.6; }
        .rd-optgroup[open] > summary .rd-chev { transform:rotate(90deg); }
        .rd-optgroup__body { padding:4px 14px 12px; }
        .rd-optgroup__help { font-size:12px; color:var(--rd-text-muted); margin:0 0 8px; }
        .rd-optrow { display:flex; align-items:center; gap:14px; padding:7px 0; border-top:1px solid var(--rd-border); }
        .rd-optrow:first-child { border-top:none; }
        .rd-optrow__label { flex:1; font-size:13px; min-width:0; }
        .rd-optrow__key { font-size:11px; color:var(--rd-text-muted); font-family:monospace; }
        .rd-optrow__ctrl { width:230px; flex:none; }
        .rd-optrow__ctrl .rd-select, .rd-optrow__ctrl .rd-input { width:100%; }
        .rd-opt-set { color:var(--rd-text-bright); }
    </style>

    {{-- Options editor + enable toggle (live-save) --}}
    <div class="rd-card" style="margin-bottom:18px;">
        <div class="rd-card__header">
            <h3 class="rd-card__title">{{ $strategy->name }}</h3>
            <a href="{{ route('admin.strategies.index') }}" class="rd-btn rd-btn--ghost"><i class="ri-arrow-left-line"></i> Back</a>
        </div>
        <div class="rd-card__body">
            <form class="rd-liveform" id="strategyForm" data-url="{{ route('admin.strategies.update', $strategy) }}" data-method="PUT">
                <div class="rd-grid rd-grid--3" style="align-items:start;">
                    <div class="rd-field">
                        <label class="rd-label" for="name">Name</label>
                        <input class="rd-input" id="name" name="name" value="{{ $strategy->name }}" required>
                    </div>
                    <div class="rd-field">
                        <label class="rd-label" for="note">Note</label>
                        <input class="rd-input" id="note" name="note" value="{{ $strategy->note }}">
                    </div>
                    <div class="rd-field">
                        <label class="rd-label" for="enabled">Status</label>
                        <select class="rd-select" id="enabled" name="enabled">
                            <option value="1" @selected($strategy->enabled)>Enabled</option>
                            <option value="0" @selected(! $strategy->enabled)>Disabled</option>
                        </select>
                    </div>
                </div>

                <label class="rd-label" style="margin-top:6px;">Configuration options pushed to clients</label>
                <p class="rd-help" style="margin-top:0;margin-bottom:12px;">
                    Leave a control on <strong>Default</strong> to keep the client's own setting — only
                    changed options are pushed. Toggles: On = enforce on, Off = enforce off.
                </p>

                @foreach ($catalog as $gi => $group)
                    <details class="rd-optgroup" @if ($gi === 0) open @endif>
                        <summary>
                            <i class="{{ $group['icon'] ?? 'ri-settings-3-line' }}"></i>
                            {{ $group['label'] }}
                            <i class="ri-arrow-right-s-line rd-chev"></i>
                        </summary>
                        <div class="rd-optgroup__body">
                            @if (!empty($group['help']))
                                <p class="rd-optgroup__help">{{ $group['help'] }}</p>
                            @endif
                            @foreach ($group['options'] as $opt)
                                @php $val = (string) ($current[$opt['key']] ?? ''); @endphp
                                <div class="rd-optrow">
                                    <div class="rd-optrow__label">
                                        {{ $opt['label'] }}
                                        <div class="rd-optrow__key">{{ $opt['key'] }}</div>
                                    </div>
                                    <div class="rd-optrow__ctrl">
                                        @if ($opt['type'] === 'toggle')
                                            <select class="rd-select @if($val!=='') rd-opt-set @endif" name="opt[{{ $opt['key'] }}]">
                                                <option value="" @selected($val==='')>Default</option>
                                                <option value="Y" @selected($val==='Y')>On</option>
                                                <option value="N" @selected($val==='N')>Off</option>
                                            </select>
                                        @elseif ($opt['type'] === 'select')
                                            <select class="rd-select @if($val!=='') rd-opt-set @endif" name="opt[{{ $opt['key'] }}]">
                                                @foreach ($opt['choices'] as $cv => $cl)
                                                    <option value="{{ $cv }}" @selected($val===(string)$cv)>{{ $cl }}</option>
                                                @endforeach
                                            </select>
                                        @elseif ($opt['type'] === 'number')
                                            <input class="rd-input @if($val!=='') rd-opt-set @endif" type="number" min="0"
                                                   name="opt[{{ $opt['key'] }}]" value="{{ $val }}" placeholder="Default">
                                        @else
                                            <input class="rd-input @if($val!=='') rd-opt-set @endif" type="text"
                                                   name="opt[{{ $opt['key'] }}]" value="{{ $val }}" placeholder="Default">
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </details>
                @endforeach

                {{-- Custom / advanced options not in the catalog --}}
                <details class="rd-optgroup" @if (!empty($customOptions)) open @endif>
                    <summary>
                        <i class="ri-terminal-box-line"></i>
                        Custom options
                        <i class="ri-arrow-right-s-line rd-chev"></i>
                    </summary>
                    <div class="rd-optgroup__body">
                        <p class="rd-optgroup__help">
                            Any other <code>config_options</code> key the client supports. Pushed verbatim.
                        </p>
                        <div id="optionRows"></div>
                        <button type="button" class="rd-btn rd-btn--ghost" id="addOption" style="margin:6px 0 0;"><i class="ri-add-line"></i> Add custom option</button>
                    </div>
                </details>

                <div class="rd-row" style="margin-top:16px;">
                    <button type="submit" class="rd-btn rd-btn--primary rd-btn--save" data-state="idle">Save</button>
                    <span class="rd-help" style="margin-left:12px;">Saving bumps the modified timestamp; clients pull within one heartbeat.</span>
                </div>
            </form>
        </div>
    </div>

    {{-- Assignments --}}
    <div class="rd-card">
        <div class="rd-card__header">
            <h3 class="rd-card__title">Assignments</h3>
        </div>
        <div class="rd-card__body">
            <form method="POST" action="{{ route('admin.strategies.assignments.store', $strategy) }}" class="rd-row" style="margin-bottom:16px;flex-wrap:wrap;">
                @csrf
                <select class="rd-select" name="target_type" id="targetType" style="width:150px;">
                    <option value="device">Device</option>
                    <option value="user">User</option>
                    <option value="device_group">Device Group</option>
                </select>

                <select class="rd-select" name="target_id" data-target="device" style="width:200px;">
                    @foreach ($devices as $d)
                        <option value="{{ $d->id }}">{{ $d->hostname ?: $d->alias ?: $d->rustdesk_id }} ({{ $d->rustdesk_id }})</option>
                    @endforeach
                </select>
                <select class="rd-select" name="target_id" data-target="user" style="width:200px;display:none;" disabled>
                    @foreach ($users as $u)
                        <option value="{{ $u->id }}">{{ $u->username }}</option>
                    @endforeach
                </select>
                <select class="rd-select" name="target_id" data-target="device_group" style="width:200px;display:none;" disabled>
                    @foreach ($deviceGroups as $g)
                        <option value="{{ $g->id }}">{{ $g->name }}</option>
                    @endforeach
                </select>

                <button type="submit" class="rd-btn rd-btn--primary"><i class="ri-add-line"></i> Assign</button>
            </form>

            <table class="rd-table">
                <thead><tr><th>Type</th><th>Target</th><th style="text-align:right;">Action</th></tr></thead>
                <tbody>
                @forelse ($strategy->assignments as $a)
                    @php
                        $label = match ($a->target_type) {
                            'device' => optional($deviceMap->get($a->target_id))->rustdesk_id ?? ('#'.$a->target_id),
                            'user' => optional($userMap->get($a->target_id))->username ?? ('#'.$a->target_id),
                            'device_group' => optional($deviceGroupMap->get($a->target_id))->name ?? ('#'.$a->target_id),
                            default => '#'.$a->target_id,
                        };
                    @endphp
                    <tr>
                        <td><span class="rd-badge rd-badge--muted">{{ $targetLabels[$a->target_type] ?? $a->target_type }}</span></td>
                        <td class="rd-muted">{{ $label }}</td>
                        <td style="text-align:right;">
                            <form method="POST" action="{{ route('admin.strategies.assignments.destroy', $a) }}" class="m-0">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="rd-btn rd-btn--danger" data-confirm="Remove this assignment?"><i class="ri-delete-bin-line"></i></button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="3" class="rd-muted" style="text-align:center;padding:20px;">No assignments yet.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    $(function () {
        // Custom (non-catalog) options only — catalog options render as native controls above.
        var customOptions = @json((object) $customOptions, JSON_UNESCAPED_SLASHES);

        function optionRow(key, value) {
            var $row = $(
                '<div class="rd-row" style="margin-bottom:8px;">' +
                '<input class="rd-input" name="option_keys[]" placeholder="key" style="flex:1;">' +
                '<input class="rd-input" name="option_values[]" placeholder="value" style="flex:1;">' +
                '<button type="button" class="rd-btn rd-btn--ghost rd-opt-remove" title="Remove"><i class="ri-close-line"></i></button>' +
                '</div>'
            );
            $row.find('input[name="option_keys[]"]').val(key || '');
            $row.find('input[name="option_values[]"]').val(value == null ? '' : String(value));
            return $row;
        }

        var $rows = $('#optionRows');
        Object.keys(customOptions).forEach(function (k) { $rows.append(optionRow(k, customOptions[k])); });

        $('#addOption').on('click', function () {
            $rows.append(optionRow('', ''));
            $('#strategyForm').trigger('change');
        });

        $rows.on('click', '.rd-opt-remove', function () {
            $(this).closest('.rd-row').remove();
            $('#strategyForm').trigger('change');
        });

        // Highlight controls that override the client default.
        $('#strategyForm').on('change', 'select[name^="opt["], input[name^="opt["]', function () {
            $(this).toggleClass('rd-opt-set', $(this).val() !== '');
        });

        // Assignment target switcher: enable only the relevant select.
        function syncTarget() {
            var type = $('#targetType').val();
            $('select[data-target]').each(function () {
                var match = $(this).data('target') === type;
                $(this).prop('disabled', !match).toggle(match);
            });
        }
        $('#targetType').on('change', syncTarget);
        syncTarget();
    });
</script>
@endpush
