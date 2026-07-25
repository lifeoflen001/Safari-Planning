@extends('settings.layout')

@section('title', 'Workspace Profile | Safari Planner')
@section('page_title', 'Workspace profile')

@section('content')
<section class="workspace-settings-grid">
    <section class="panel settings-card">
        <div class="panel-heading">
            <div class="settings-heading-icon settings-heading-icon-workspace">⌂</div>
            <div class="settings-section-head">
                <h2>Workspace profile</h2>
                <p>Keep the details your team and partners see across Safari Planner up to date.</p>
            </div>
        </div>
        <form method="POST" action="{{ route('settings.workspace.update') }}" class="settings-form">
            @csrf
            <div class="settings-fields">
                <label>Workspace name <span class="required">*</span>
                    <input type="text" name="name" value="{{ old('name', $company?->name) }}" placeholder="e.g. Tanzania Specialist" required maxlength="160">
                </label>
                <label>Country
                    <input type="text" name="country" value="{{ old('country', $company?->country) }}" placeholder="e.g. Tanzania" maxlength="120">
                </label>
                <label>Logo URL
                    <input type="url" name="logo_url" value="{{ old('logo_url', $company?->logo_url) }}" placeholder="https://example.com/logo.png" maxlength="2048">
                </label>
                <label>Website
                    <input type="url" name="website" value="{{ old('website', $company?->website) }}" placeholder="https://example.com" maxlength="2048">
                </label>
                <label>Contact email
                    <input type="email" name="contact_email" value="{{ old('contact_email', $company?->contact_email) }}" placeholder="operations@example.com">
                </label>
                <label>Contact phone
                    <input type="text" name="contact_phone" value="{{ old('contact_phone', $company?->contact_phone) }}" placeholder="+255 700 000 000" maxlength="40">
                </label>
                <label class="settings-field-wide">Description
                    <textarea name="description" rows="5" maxlength="1000" placeholder="A short description of your safari workspace.">{{ old('description', $company?->description) }}</textarea>
                    <small>Shown in workspace summaries and internal profile areas.</small>
                </label>
            </div>
            <div class="settings-form-actions">
                <button type="reset" class="button button-secondary">Reset</button>
                <button type="submit" class="button button-primary">Save workspace profile</button>
            </div>
        </form>
    </section>

    <aside class="workspace-preview panel">
        <div class="workspace-preview-topline"><span class="eyebrow">Workspace preview</span><span class="status-pill status-green">Admin managed</span></div>
        <div class="workspace-preview-logo" id="workspaceLogoPreview">
            @if($company?->logo_url)
                <img src="{{ $company->logo_url }}" alt="{{ $company->name }} logo">
            @else
                {{ collect(preg_split('/\s+/', trim($company?->name ?? 'TS')))->map(fn ($part) => strtoupper(substr($part, 0, 1)))->take(2)->implode('') }}
            @endif
        </div>
        <h3 id="workspacePreviewName">{{ $company?->name ?? 'Your workspace' }}</h3>
        <p id="workspacePreviewDescription">{{ $company?->description ?: 'Add a description to help your team recognize this workspace.' }}</p>
        <div class="workspace-preview-meta">
            <span id="workspacePreviewCountry">{{ $company?->country ?: 'Country not set' }}</span>
            <span>{{ $company?->contact_email ?: 'Contact email not set' }}</span>
        </div>
        <p class="workspace-preview-note">Only workspace administrators can edit these details.</p>
    </aside>
</section>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const name = document.querySelector('[name="name"]');
        const country = document.querySelector('[name="country"]');
        const description = document.querySelector('[name="description"]');
        const namePreview = document.getElementById('workspacePreviewName');
        const countryPreview = document.getElementById('workspacePreviewCountry');
        const descriptionPreview = document.getElementById('workspacePreviewDescription');

        name?.addEventListener('input', () => { namePreview.textContent = name.value || 'Your workspace'; });
        country?.addEventListener('input', () => { countryPreview.textContent = country.value || 'Country not set'; });
        description?.addEventListener('input', () => { descriptionPreview.textContent = description.value || 'Add a description to help your team recognize this workspace.'; });
    });
</script>
@endpush
