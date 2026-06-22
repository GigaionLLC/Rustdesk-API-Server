@extends('layouts.admin')
@section('title', 'New Device Group')

@section('content')
    <div class="rd-breadcrumb">Management / Device Groups / New</div>

    <div class="rd-card" style="max-width:560px;">
        <div class="rd-card__header">
            <h3 class="rd-card__title">New device group</h3>
            <a href="{{ route('admin.device-groups.index') }}" class="rd-btn rd-btn--ghost"><i class="ri-arrow-left-line"></i> Back</a>
        </div>
        <div class="rd-card__body">
            @if ($errors->any())
                <div class="rd-toast rd-toast--error" style="margin-bottom:16px;">
                    <i class="ri-error-warning-line"></i><span>{{ $errors->first() }}</span>
                </div>
            @endif
            <form method="POST" action="{{ route('admin.device-groups.store') }}">
                @csrf
                <div class="rd-field">
                    <label class="rd-label" for="name">Name</label>
                    <input class="rd-input" id="name" name="name" value="{{ old('name') }}" required>
                </div>
                <div class="rd-field">
                    <label class="rd-label" for="note">Note</label>
                    <input class="rd-input" id="note" name="note" value="{{ old('note') }}">
                </div>
                <div class="rd-row" style="margin-top:8px;">
                    <button type="submit" class="rd-btn rd-btn--primary"><i class="ri-save-line"></i> Create device group</button>
                </div>
            </form>
        </div>
    </div>
@endsection
