{{--
    Shared fields for the admin-role create/edit forms. Expects $role (an AdminRole, possibly
    unsaved), $groups (collection for the group-scope multi-select), and $selectedPerms /
    $selectedScope arrays of the currently chosen permission strings and group ids.
--}}
<div class="rd-field">
    <label class="rd-label" for="name">Name</label>
    <input class="rd-input" id="name" name="name" value="{{ old('name', $role->name) }}" required>
</div>

<div class="rd-field">
    <label class="rd-label" for="type">Type</label>
    <select class="rd-select" id="type" name="type" data-role-type>
        <option value="{{ \App\Models\AdminRole::TYPE_GLOBAL }}" @selected(old('type', $role->type) === \App\Models\AdminRole::TYPE_GLOBAL)>Global (full access)</option>
        <option value="{{ \App\Models\AdminRole::TYPE_INDIVIDUAL }}" @selected(old('type', $role->type) === \App\Models\AdminRole::TYPE_INDIVIDUAL)>Individual (own devices &amp; logs)</option>
        <option value="{{ \App\Models\AdminRole::TYPE_GROUP }}" @selected(old('type', $role->type) === \App\Models\AdminRole::TYPE_GROUP)>Group-scoped</option>
    </select>
    <span class="rd-help">A Global role implies every permission regardless of the grid below.</span>
</div>

<div class="rd-field" data-role-scope @if(old('type', $role->type) !== \App\Models\AdminRole::TYPE_GROUP) style="display:none;" @endif>
    <label class="rd-label" for="scope">Scoped user groups</label>
    <select class="rd-select" id="scope" name="scope[]" multiple size="6">
        @foreach ($groups as $g)
            <option value="{{ $g->id }}" @selected(in_array((int) $g->id, $selectedScope, true))>{{ $g->name }}</option>
        @endforeach
    </select>
    <span class="rd-help">For group-scoped roles, the user/device groups this role applies to.</span>
</div>

<div class="rd-field">
    <label class="rd-label">Permissions</label>
    <span class="rd-help" style="margin-bottom:10px;display:block;">Select the console areas and actions this role may use.</span>
    <div class="rd-grid rd-grid--2" data-role-perms>
        @foreach (\App\Models\AdminRole::PERMISSION_CATALOG as $area => $perms)
            <div class="rd-card" style="margin:0;">
                <div class="rd-card__body" style="padding:12px 14px;">
                    <div style="color:var(--rd-text-bright);font-weight:600;margin-bottom:8px;">{{ $area }}</div>
                    @foreach ($perms as $perm)
                        <label class="rd-row" style="gap:8px;margin-bottom:4px;cursor:pointer;">
                            <input type="checkbox" name="perms[]" value="{{ $perm }}" @checked(in_array($perm, $selectedPerms, true))>
                            <span class="rd-muted">{{ \Illuminate\Support\Str::headline(\Illuminate\Support\Str::afterLast($perm, '.')) }}</span>
                        </label>
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>
</div>

@push('scripts')
<script>
    $(function () {
        // Show the group-scope picker only for the group type; the permission grid is ignored
        // for global roles (which imply everything) but stays editable for clarity.
        var $type = $('select[data-role-type]');
        function syncScope() {
            $('[data-role-scope]').toggle($type.val() === '{{ \App\Models\AdminRole::TYPE_GROUP }}');
        }
        $type.on('change', syncScope);
        syncScope();
    });
</script>
@endpush
