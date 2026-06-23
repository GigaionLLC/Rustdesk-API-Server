@extends('layouts.admin')
@section('title', 'Webhook deliveries')

@section('content')
    @include('admin.partials.flash')
    <div class="rd-breadcrumb">System / Webhooks / {{ $webhook->name }} / Deliveries</div>

    <div class="rd-card" style="margin-bottom:16px;">
        <div class="rd-card__body" style="display:flex;align-items:center;gap:14px;flex-wrap:wrap;">
            <strong style="color:var(--rd-text-bright);">{{ $webhook->name }}</strong>
            <span class="rd-muted" style="font-size:12px;max-width:340px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ $webhook->url }}</span>
            <a href="{{ route('admin.webhooks.index') }}" class="rd-btn rd-btn--ghost" style="margin-left:auto;"><i class="ri-arrow-left-line"></i> Back to webhooks</a>
        </div>
    </div>

    <div class="rd-card">
        <div class="rd-card__header"><h3 class="rd-card__title">Recent deliveries</h3></div>
        <div class="rd-card__body" style="padding:0;">
            <table class="rd-table">
                <thead><tr><th>Event</th><th>Status</th><th>Code</th><th>Attempts</th><th>When</th><th>Next retry</th><th>Error</th><th style="text-align:right;">Action</th></tr></thead>
                <tbody>
                @forelse ($deliveries as $d)
                    <tr>
                        <td><code style="font-size:12px;">{{ $d->event }}</code></td>
                        <td>
                            @php($cls = $d->status === \App\Models\WebhookDelivery::STATUS_SUCCESS ? 'rd-badge--online' : ($d->status === \App\Models\WebhookDelivery::STATUS_FAILED ? 'rd-badge--offline' : 'rd-badge--muted'))
                            <span class="rd-badge {{ $cls }}">{{ $d->status }}</span>
                        </td>
                        <td class="rd-muted">{{ $d->status_code ?? '—' }}</td>
                        <td class="rd-muted">{{ $d->attempts }}</td>
                        <td class="rd-muted">{{ $d->created_at?->diffForHumans() }}</td>
                        <td class="rd-muted">{{ $d->next_attempt_at?->diffForHumans() ?? '—' }}</td>
                        <td class="rd-muted" style="max-width:220px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="{{ $d->error }}">{{ $d->error ?? '—' }}</td>
                        <td style="text-align:right;">
                            @if ($d->status !== \App\Models\WebhookDelivery::STATUS_SUCCESS)
                                <form method="POST" action="{{ route('admin.webhooks.deliveries.resend', $d) }}" class="m-0">
                                    @csrf
                                    <button type="submit" class="rd-btn rd-btn--ghost" title="Resend now"><i class="ri-refresh-line"></i> Resend</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="rd-muted" style="text-align:center;padding:24px;">No deliveries recorded yet.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div style="margin-top:14px;">@include('admin.partials.pagination', ['paginator' => $deliveries])</div>
@endsection
