@extends('settings.layout')

@section('title', 'Business Information | Safari Planner')

@section('content')
<section class="settings-layout-single">
    <section class="panel">
        <div class="panel-heading">
            <div>
                <h2>Business Information</h2>
                <p>Legal and operational details for your safari business.</p>
            </div>
        </div>
        <form method="POST" action="#" class="settings-form">
            @csrf
            <div class="settings-fields">
                <label>Legal Name <span class="required">*</span>
                    <input type="text" value="{{ $company->legal_name ?? ($company->name ?? '') }}" required>
                </label>
                <label>Registration Number
                    <input type="text" value="{{ $company->registration_number ?? '' }}">
                </label>
                <label>Tax ID
                    <input type="text" value="{{ $company->tax_id ?? '' }}">
                </label>
                <label>Address
                    <textarea rows="3">{{ $company->address ?? '' }}</textarea>
                </label>
                <label>Phone
                    <input type="text" value="{{ $company->phone ?? '' }}">
                </label>
                <label>Email
                    <input type="email" value="{{ $company->email ?? '' }}">
                </label>
            </div>
            <div class="settings-form-actions">
                <button type="submit" class="button button-primary">Save business information</button>
            </div>
        </form>
    </section>
</section>
@endsection