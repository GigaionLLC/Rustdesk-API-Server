@extends('layouts.admin')
@section('title', 'New Admin Role')

@php
    $selectedPerms = old('perms', []);
    $selectedScope = array_map('intval', old('scope', []));
@endphp

@section('content')
    <div class="rd-breadcrumb">System / Admin Roles / New</div>

    <div class="rd-card" style="max-width:880px;">
        <div class="rd-card__header">
            <h3 class="rd-card__title">New role</h3>
            <a href="{{ route('admin.roles.index') }}" class="rd-btn rd-btn--ghost"><i class="ri-arrow-left-line"></i> Back</a>
        </div>
        <div class="rd-card__body">
            @if ($errors->any())
                <div class="rd-toast rd-toast--error" style="margin-bottom:16px;">
                    <i class="ri-error-warning-line"></i><span>{{ $errors->first() }}</span>
                </div>
            @endif

            <form method="POST" action="{{ route('admin.roles.store') }}">
                @csrf
                @include('admin.admin_roles._form')
                <div class="rd-row" style="margin-top:8px;">
                    <button type="submit" class="rd-btn rd-btn--primary"><i class="ri-save-line"></i> Create role</button>
                </div>
            </form>
        </div>
    </div>
@endsection
