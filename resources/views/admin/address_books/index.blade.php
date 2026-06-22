@extends('layouts.admin')
@section('title', 'Address Books')

@section('content')
    @include('admin.partials.flash')
    <div class="rd-breadcrumb">Management / Address Books</div>

    <div class="rd-card">
        <div class="rd-card__header">
            <h3 class="rd-card__title">Address Books</h3>
        </div>
        <div class="rd-card__body" style="padding:0;">
            <table class="rd-table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Owner</th>
                        <th>Peers</th>
                        <th>Tags</th>
                        <th style="text-align:right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                @forelse ($addressBooks as $book)
                    <tr>
                        <td style="color:var(--rd-text-bright);font-weight:600;">{{ $book->name ?: 'Default' }}</td>
                        <td class="rd-muted">{{ $book->user->username ?? '—' }}</td>
                        <td class="rd-muted">{{ $book->peers_count }}</td>
                        <td class="rd-muted">{{ $book->tags_count }}</td>
                        <td style="text-align:right;">
                            <div class="rd-row" style="justify-content:flex-end;">
                                <a href="{{ route('admin.address-books.show', $book) }}" class="rd-btn rd-btn--ghost"><i class="ri-eye-line"></i> View</a>
                                <form method="POST" action="{{ route('admin.address-books.destroy', $book) }}" class="m-0">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="rd-btn rd-btn--danger" data-confirm="Delete this address book and all its peers/tags?"><i class="ri-delete-bin-line"></i></button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="rd-muted" style="text-align:center;padding:28px;">No address books yet.</td></tr>
                @endforelse
                </tbody>
            </table>
            @include('admin.partials.pagination', ['paginator' => $addressBooks])
        </div>
    </div>
@endsection
