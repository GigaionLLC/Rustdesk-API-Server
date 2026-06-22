@extends('layouts.admin')
@section('title', 'New Group')

@section('content')
    <div class="rd-breadcrumb">Management / Groups / New</div>

    <div class="rd-card" style="max-width:560px;">
        <div class="rd-card__header">
            <h3 class="rd-card__title">New group</h3>
            <a href="{{ route('admin.groups.index') }}" class="rd-btn rd-btn--ghost"><i class="ri-arrow-left-line"></i> Back</a>
        </div>
        <div class="rd-card__body">
            @if ($errors->any())
                <div class="rd-toast rd-toast--error" style="margin-bottom:16px;">
                    <i class="ri-error-warning-line"></i><span>{{ $errors->first() }}</span>
                </div>
            @endif
            <form method="POST" action="{{ route('admin.groups.store') }}">
                @csrf
                <div class="rd-field">
                    <label class="rd-label" for="name">Name</label>
                    <input class="rd-input" id="name" name="name" value="{{ old('name') }}" required>
                </div>
                <div class="rd-field">
                    <label class="rd-label" for="type">Type</label>
                    <select class="rd-select" id="type" name="type">
                        <option value="{{ \App\Models\Group::TYPE_DEFAULT }}" @selected(old('type', \App\Models\Group::TYPE_DEFAULT) == \App\Models\Group::TYPE_DEFAULT)>Default</option>
                        <option value="{{ \App\Models\Group::TYPE_SHARED }}" @selected(old('type') == \App\Models\Group::TYPE_SHARED)>Shared</option>
                    </select>
                </div>
                <div class="rd-field">
                    <label class="rd-label" for="note">Note</label>
                    <input class="rd-input" id="note" name="note" value="{{ old('note') }}">
                </div>
                <div class="rd-row" style="margin-top:8px;">
                    <button type="submit" class="rd-btn rd-btn--primary"><i class="ri-save-line"></i> Create group</button>
                </div>
            </form>
        </div>
    </div>
@endsection
