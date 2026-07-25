@php
    $isGuide = $resourceType === 'guides';
    $pageTitle = $isGuide ? $resource->name : $resource->registration_number;
    $pageType = $isGuide ? 'Guide profile' : 'Vehicle profile';
    $backRoute = $isGuide ? 'planner.guides' : 'planner.vehicles';
    $statusLabel = ucfirst(str_replace('_', ' ', $resource->status));
@endphp

@extends('layouts.app')

@section('title', $pageTitle . ' | Safari Planner')
@section('page_title', $pageTitle)

@section('content')
<section class="page-header resource-profile-header">
    <div>
        <a class="profile-back-link" href="{{ route($backRoute) }}">&larr; Back to {{ $isGuide ? 'guides' : 'vehicles' }}</a>
        <div class="eyebrow">Operations / {{ $pageType }}</div>
        <h1>{{ $pageTitle }}</h1>
        <p class="lede">{{ $isGuide ? 'Contact, licensing and language details for this guide.' : 'Registration, capacity and service details for this vehicle.' }}</p>
    </div>
    <div class="page-action">
        <button class="button button-secondary" type="button" onclick="showToast('{{ $isGuide ? 'Guide' : 'Vehicle' }} editing is ready for the next workflow')">Edit profile</button>
    </div>
</section>

<div class="resource-profile-layout">
    <section class="panel resource-profile-card">
        <div class="resource-profile-hero">
            @if($isGuide)
                <span class="avatar avatar-sage resource-profile-avatar">{{ collect(explode(' ', $resource->name))->map(fn ($part) => substr($part, 0, 1))->take(2)->implode('') }}</span>
                <div>
                    <h2>{{ $resource->name }}</h2>
                    <p>{{ $resource->email ?: 'Email not added' }}</p>
                </div>
            @else
                <span class="metric-icon metric-blue resource-profile-vehicle-icon">4x4</span>
                <div>
                    <h2>{{ $resource->registration_number }}</h2>
                    <p>{{ $resource->type }}</p>
                </div>
            @endif
            <span class="status-pill status-{{ $resource->status === 'available' ? 'green' : 'blue' }}">{{ $statusLabel }}</span>
        </div>

        <div class="resource-detail-grid">
            @if($isGuide)
                <div><span>Phone</span><strong>{{ $resource->phone ?: 'Not added' }}</strong></div>
                <div><span>Email</span><strong>{{ $resource->email ?: 'Not added' }}</strong></div>
                <div><span>Languages</span><strong>{{ implode(', ', $resource->languages ?: ['Not specified']) }}</strong></div>
                <div><span>License number</span><strong>{{ $resource->license_number ?: 'Not added' }}</strong></div>
                <div><span>License expiry</span><strong>{{ $resource->license_expiry?->format('d M Y') ?: 'No expiry date' }}</strong></div>
                <div><span>Added</span><strong>{{ $resource->created_at?->format('d M Y') ?: 'Not available' }}</strong></div>
            @else
                <div><span>Registration</span><strong>{{ $resource->registration_number }}</strong></div>
                <div><span>Vehicle type</span><strong>{{ $resource->type }}</strong></div>
                <div><span>Passenger capacity</span><strong>{{ $resource->capacity }} guests</strong></div>
                <div><span>Last service</span><strong>{{ $resource->last_service_at?->format('d M Y') ?: 'Not recorded' }}</strong></div>
                <div><span>Next service</span><strong>{{ $resource->next_service_at?->format('d M Y') ?: 'Not scheduled' }}</strong></div>
                <div><span>Added</span><strong>{{ $resource->created_at?->format('d M Y') ?: 'Not available' }}</strong></div>
            @endif
        </div>
    </section>

    <aside class="panel resource-profile-side">
        <div class="resource-profile-side-icon">{{ $isGuide ? 'G' : 'V' }}</div>
        <h2>Operational readiness</h2>
        <p>{{ $isGuide ? 'Use this profile to confirm contact, language coverage and licensing before assigning the guide.' : 'Use this profile to confirm capacity and service dates before assigning the vehicle.' }}</p>
        <a class="profile-link" href="{{ route($backRoute) }}">Return to {{ $isGuide ? 'guides' : 'vehicles' }} &rarr;</a>
    </aside>
</div>
@endsection
