{{-- Renders a session flash 'status' message as a success toast after load. --}}
@if (session('status'))
    @push('scripts')
        <script>$(function () { RD.toast(@json(session('status')), 'success'); });</script>
    @endpush
@endif
