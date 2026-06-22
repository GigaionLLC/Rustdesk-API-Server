@extends('layouts.admin')
@section('title', 'Edit Admin Role')

@php
    $selectedPerms = old('perms', (array) $role->perms);
    $selectedScope = array_map('intval', old('scope', (array) $role->scope));
@endphp

@section('content')
    <div class="rd-breadcrumb">System / Admin Roles / Edit</div>

    <div class="rd-card" style="max-width:880px;">
        <div class="rd-card__header">
            <h3 class="rd-card__title">{{ $role->name }}</h3>
            <a href="{{ route('admin.roles.index') }}" class="rd-btn rd-btn--ghost"><i class="ri-arrow-left-line"></i> Back</a>
        </div>
        <div class="rd-card__body">
            @if ($errors->any())
                <div class="rd-toast rd-toast--error" style="margin-bottom:16px;">
                    <i class="ri-error-warning-line"></i><span>{{ $errors->first() }}</span>
                </div>
            @endif

            <form method="POST" action="{{ route('admin.roles.update', $role) }}">
                @csrf
                @method('PUT')
                @include('admin.admin_roles._form')
                <div class="rd-row" style="margin-top:8px;">
                    <button type="submit" class="rd-btn rd-btn--primary"><i class="ri-save-line"></i> Save</button>
                </div>
            </form>
        </div>
    </div>
@endsection
