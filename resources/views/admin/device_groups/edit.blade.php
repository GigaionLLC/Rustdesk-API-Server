@extends('layouts.admin')
@section('title', 'Edit Device Group')

@section('content')
    <div class="rd-breadcrumb">Management / Device Groups / Edit</div>

    <div class="rd-card" style="max-width:560px;">
        <div class="rd-card__header">
            <h3 class="rd-card__title">{{ $deviceGroup->name }}</h3>
            <a href="{{ route('admin.device-groups.index') }}" class="rd-btn rd-btn--ghost"><i class="ri-arrow-left-line"></i> Back</a>
        </div>
        <div class="rd-card__body">
            <form class="rd-liveform" data-url="{{ route('admin.device-groups.update', $deviceGroup) }}" data-method="PUT">
                <div class="rd-field">
                    <label class="rd-label" for="name">Name</label>
                    <input class="rd-input" id="name" name="name" value="{{ $deviceGroup->name }}" required>
                </div>
                <div class="rd-field">
                    <label class="rd-label" for="note">Note</label>
                    <input class="rd-input" id="note" name="note" value="{{ $deviceGroup->note }}">
                </div>
                <div class="rd-field">
                    <label class="rd-label" for="access_groups">Accessible by these user groups</label>
                    <select class="rd-select" id="access_groups" multiple size="6" data-access-multiselect data-target="#access_group_ids">
                        @foreach ($userGroups as $g)
                            <option value="{{ $g->id }}" @selected(in_array((int) $g->id, $accessGroupIds, true))>{{ $g->name }}</option>
                        @endforeach
                    </select>
                    <input type="hidden" id="access_group_ids" name="access_group_ids" value="{{ implode(',', $accessGroupIds) }}">
                    <small class="rd-help">Members of the selected user groups may access devices in this device group.</small>
                </div>
                <div class="rd-row" style="margin-top:8px;">
                    <button type="submit" class="rd-btn rd-btn--primary rd-btn--save" data-state="idle">Save</button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    $(function () {
        // Mirror the multi-select selection into a hidden CSV field so the live-save form
        // (which flattens array inputs) submits the full set.
        $('select[data-access-multiselect]').each(function () {
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
