@extends('layouts.admin')
@section('title', 'Recordings')

@section('content')
    @include('admin.partials.flash')
    <div class="rd-breadcrumb">Audit / Recordings</div>

    <div class="rd-card">
        <div class="rd-card__header">
            <h3 class="rd-card__title">Recordings</h3>
            <form method="GET" action="{{ route('admin.recordings.index') }}" class="rd-row">
                <input class="rd-input" type="search" name="q" value="{{ $q }}" placeholder="Search peer / file" style="width:220px;">
                <input class="rd-input" type="search" name="status" value="{{ $status }}" placeholder="Status" style="width:140px;">
                <button class="rd-btn rd-btn--ghost" type="submit"><i class="ri-search-line"></i> Search</button>
            </form>
        </div>
        <div class="rd-card__body" style="padding:0;">
            <table class="rd-table">
                <thead>
                    <tr>
                        <th>Peer</th>
                        <th>Filename</th>
                        <th>Size</th>
                        <th>Status</th>
                        <th>Started</th>
                        <th>Finished</th>
                        <th style="text-align:right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                @forelse ($recordings as $recording)
                    @php
                        $bytes = (int) $recording->size;
                        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
                        $i = 0;
                        $human = $bytes;
                        while ($human >= 1024 && $i < count($units) - 1) {
                            $human /= 1024;
                            $i++;
                        }
                        $sizeLabel = $i === 0 ? $bytes.' B' : number_format($human, 1).' '.$units[$i];
                    @endphp
                    <tr>
                        <td style="color:var(--rd-text-bright);">{{ $recording->peer_id }}</td>
                        <td class="rd-muted">{{ $recording->filename }}</td>
                        <td class="rd-muted">{{ $sizeLabel }}</td>
                        <td><span class="rd-badge rd-badge--muted">{{ $recording->status }}</span></td>
                        <td class="rd-muted">{{ $recording->started_at?->format('Y-m-d H:i:s') ?? '—' }}</td>
                        <td class="rd-muted">{{ $recording->finished_at?->format('Y-m-d H:i:s') ?? '—' }}</td>
                        <td style="text-align:right;">
                            <div class="rd-row" style="justify-content:flex-end;">
                                <a href="{{ route('admin.recordings.download', $recording) }}" class="rd-btn rd-btn--ghost"><i class="ri-download-2-line"></i> Download</a>
                                <form method="POST" action="{{ route('admin.recordings.destroy', $recording) }}" class="m-0">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="rd-btn rd-btn--danger" data-confirm="Delete this recording? The file will be removed."><i class="ri-delete-bin-line"></i></button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="rd-muted" style="text-align:center;padding:28px;">No recordings.</td></tr>
                @endforelse
                </tbody>
            </table>
            @include('admin.partials.pagination', ['paginator' => $recordings])
        </div>
    </div>
@endsection
