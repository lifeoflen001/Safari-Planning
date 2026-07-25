@extends('layouts.app')

@section('title', 'Dashboard | Safari Planner')
@section('page_title', 'Dashboard')

@section('content')
<div id="overviewDashboard">
    @include('planner.partials.control-centre', $dashboard ?? [])
</div>
@endsection

@push('scripts')
<script>
    const overviewRefreshUrl = @json(route('planner.overview'));

    window.refreshOverview = async function refreshOverview() {
        if (document.hidden) return;
        try {
            const response = await fetch(overviewRefreshUrl, {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                credentials: 'same-origin'
            });
            if (!response.ok) return;
            const payload = await response.json();
            const target = document.getElementById('overviewDashboard');
            if (target && payload.dashboard_html) target.innerHTML = payload.dashboard_html;
            document.body.dataset.overviewRefreshedAt = payload.refreshed_at || '';
        } catch (error) {
            // A missed poll should not interrupt the operations desk.
        }
    };

    setInterval(window.refreshOverview, 30000);
</script>
@endpush
