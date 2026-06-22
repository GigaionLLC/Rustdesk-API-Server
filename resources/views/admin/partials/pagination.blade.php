{{--
    Dark-theme pagination control. Pass a LengthAwarePaginator as $paginator.
    Preserves existing query-string filters via appends() done in the controller.
    Uses only rd-* components + inline layout styles (consistent with dashboard.blade.php).
--}}
@if ($paginator->hasPages())
    <div class="rd-row" style="justify-content:space-between;padding:14px 16px;border-top:1px solid var(--rd-border);">
        <div class="rd-muted" style="font-size:13px;">
            Showing {{ $paginator->firstItem() ?? 0 }}–{{ $paginator->lastItem() ?? 0 }}
            of {{ $paginator->total() }}
        </div>
        <div class="rd-row">
            @if ($paginator->onFirstPage())
                <button class="rd-btn rd-btn--ghost" disabled><i class="ri-arrow-left-s-line"></i> Prev</button>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" class="rd-btn rd-btn--ghost"><i class="ri-arrow-left-s-line"></i> Prev</a>
            @endif

            <span class="rd-muted" style="font-size:13px;">Page {{ $paginator->currentPage() }} / {{ $paginator->lastPage() }}</span>

            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" class="rd-btn rd-btn--ghost">Next <i class="ri-arrow-right-s-line"></i></a>
            @else
                <button class="rd-btn rd-btn--ghost" disabled>Next <i class="ri-arrow-right-s-line"></i></button>
            @endif
        </div>
    </div>
@endif
