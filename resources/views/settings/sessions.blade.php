@extends('settings.layout')

@section('title', 'Activity & Sessions | Safari Planner')

@section('content')
<section class="settings-layout-single">
    <section class="panel">
        <div class="panel-heading">
            <div>
                <h2>Activity & Sessions</h2>
                <p>Review your recent login history and active sessions.</p>
            </div>
        </div>
        <div class="booking-table wide">
            <div class="booking-row booking-head">
                <span>Date & time</span>
                <span>Device</span>
                <span>Browser</span>
                <span>IP address</span>
                <span>Location</span>
                <span>Status</span>
            </div>
            @forelse($loginHistory as $entry)
                <div class="booking-row">
                    <span>{{ $entry->logged_in_at->format('d M Y H:i') }}</span>
                    <span>{{ $entry->device ?: 'Unknown' }}</span>
                    <span>{{ $entry->browser ?: 'Unknown' }}</span>
                    <span>{{ $entry->ip_address ?: '—' }}</span>
                    <span>{{ $entry->location ?: '—' }}</span>
                    <span>
                        <span class="status-pill status-{{ $entry->success ? 'green' : 'rose' }}">
                            {{ $entry->success ? 'Success' : 'Failed' }}
                        </span>
                    </span>
                </div>
            @empty
                <div class="booking-row">
                    <span colspan="6">No activity recorded yet.</span>
                </div>
            @endforelse
        </div>
        <div style="padding: 16px 20px;">
            {{ $loginHistory->links() }}
        </div>
    </section>
</section>
@endsection