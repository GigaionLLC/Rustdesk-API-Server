@extends('layouts.admin')
@section('title', 'Edit Strategy')

@php
    // Compute the options array in PHP (never build inline arrays inside @json()).
    $optionsArray = $strategy->options ?? [];
    $targetLabels = [
        \App\Models\StrategyAssignment::TARGET_DEVICE => 'Device',
        \App\Models\StrategyAssignment::TARGET_USER => 'User',
        \App\Models\StrategyAssignment::TARGET_DEVICE_GROUP => 'Device Group',
    ];
@endphp

@section('content')
    @include('admin.partials.flash')
    <div class="rd-breadcrumb">Control / Strategies / {{ $strategy->name }}</div>

    <div class="rd-grid rd-grid--2" style="align-items:start;">
        {{-- Options editor + enable toggle (live-save) --}}
        <div class="rd-card">
            <div class="rd-card__header">
                <h3 class="rd-card__title">{{ $strategy->name }}</h3>
                <a href="{{ route('admin.strategies.index') }}" class="rd-btn rd-btn--ghost"><i class="ri-arrow-left-line"></i> Back</a>
            </div>
            <div class="rd-card__body">
                <form class="rd-liveform" id="strategyForm" data-url="{{ route('admin.strategies.update', $strategy) }}" data-method="PUT">
                    <div class="rd-field">
                        <label class="rd-label" for="name">Name</label>
                        <input class="rd-input" id="name" name="name" value="{{ $strategy->name }}" required>
                    </div>
                    <div class="rd-field">
                        <label class="rd-label" for="note">Note</label>
                        <input class="rd-input" id="note" name="note" value="{{ $strategy->note }}">
                    </div>
                    <div class="rd-field">
                        <label class="rd-label" for="enabled">Enabled</label>
                        <select class="rd-select" id="enabled" name="enabled">
                            <option value="1" @selected($strategy->enabled)>Enabled</option>
                            <option value="0" @selected(! $strategy->enabled)>Disabled</option>
                        </select>
                        <span class="rd-help">Disabled strategies are not pushed to clients.</span>
                    </div>

                    <label class="rd-label">Configuration options</label>
                    <div id="optionRows">
                        {{-- rows injected by JS --}}
                    </div>
                    <button type="button" class="rd-btn rd-btn--ghost" id="addOption" style="margin:6px 0 14px;"><i class="ri-add-line"></i> Add option</button>

                    <div class="rd-row">
                        <button type="submit" class="rd-btn rd-btn--primary rd-btn--save" data-state="idle">Save</button>
                    </div>
                    <span class="rd-help" style="margin-top:8px;display:block;">Saving bumps the modified timestamp; clients pull within one heartbeat.</span>
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
    </div>
@endsection

@push('scripts')
<script>
    $(function () {
        var options = @json($optionsArray, JSON_UNESCAPED_SLASHES);

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
        var keys = Object.keys(options);
        if (keys.length === 0) {
            $rows.append(optionRow('', ''));
        } else {
            keys.forEach(function (k) { $rows.append(optionRow(k, options[k])); });
        }

        $('#addOption').on('click', function () {
            var $r = optionRow('', '');
            $rows.append($r);
            $('#strategyForm').trigger('change');
        });

        $rows.on('click', '.rd-opt-remove', function () {
            $(this).closest('.rd-row').remove();
            $('#strategyForm').trigger('change');
        });

        // Assignment target switcher: enable only the relevant select so the
        // correct target_id is submitted.
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
