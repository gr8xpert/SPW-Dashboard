@extends('layouts.client')

@section('title', 'Settings')

@section('page-content')
<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h4 class="mb-1 fw-bold"><i class="bi bi-gear me-2 text-primary"></i>Settings</h4>
        <p class="text-muted mb-0">Manage your account preferences and API access</p>
    </div>
</div>

<div class="row g-4">

    {{-- General Settings --}}
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-bottom pt-4 pb-3">
                <h6 class="fw-bold mb-0"><i class="bi bi-sliders me-2 text-primary"></i>General Settings</h6>
            </div>
            <div class="card-body p-4">
                <form method="POST" action="{{ route('dashboard.settings.update') }}">
                    @csrf
                    @method('PUT')

                    <div class="row g-3">

                        {{-- Company Name --}}
                        <div class="col-12">
                            <label class="form-label fw-medium">Company Name <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-building"></i></span>
                                <input type="text"
                                       name="company_name"
                                       class="form-control @error('company_name') is-invalid @enderror"
                                       value="{{ old('company_name', $client->company_name) }}"
                                       placeholder="Your Company Name"
                                       required>
                            </div>
                            @error('company_name')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Timezone --}}
                        <div class="col-12">
                            <label class="form-label fw-medium">Timezone</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-clock"></i></span>
                                <select name="timezone"
                                        class="form-select @error('timezone') is-invalid @enderror">
                                    @php
                                        $timezones = [
                                            'UTC'                => 'UTC (Coordinated Universal Time)',
                                            'America/New_York'   => 'America/New_York (ET)',
                                            'America/Chicago'    => 'America/Chicago (CT)',
                                            'America/Denver'     => 'America/Denver (MT)',
                                            'America/Los_Angeles'=> 'America/Los_Angeles (PT)',
                                            'Europe/London'      => 'Europe/London (GMT/BST)',
                                            'Europe/Paris'       => 'Europe/Paris (CET/CEST)',
                                            'Asia/Dubai'         => 'Asia/Dubai (GST)',
                                            'Asia/Karachi'       => 'Asia/Karachi (PKT)',
                                            'Asia/Kolkata'       => 'Asia/Kolkata (IST)',
                                            'Asia/Singapore'     => 'Asia/Singapore (SGT)',
                                            'Australia/Sydney'   => 'Australia/Sydney (AEST/AEDT)',
                                            'Pacific/Auckland'   => 'Pacific/Auckland (NZST/NZDT)',
                                        ];
                                        $currentTz = old('timezone', $settings['timezone'] ?? $client->timezone ?? 'UTC');
                                    @endphp
                                    @foreach($timezones as $value => $label)
                                        <option value="{{ $value }}" {{ $currentTz === $value ? 'selected' : '' }}>
                                            {{ $label }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            @error('timezone')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                    </div>

                    <hr class="my-4">

                    <div class="d-flex justify-content-end">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-floppy me-1"></i> Save Settings
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Right column: plan info + API Access --}}
    <div class="col-lg-5">

        {{-- Current Plan Info --}}
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white border-bottom pt-4 pb-3">
                <h6 class="fw-bold mb-0"><i class="bi bi-gem me-2 text-primary"></i>Current Plan</h6>
            </div>
            <div class="card-body p-4">
                <div class="d-flex align-items-center gap-3 mb-3">
                    <div class="rounded-3 bg-primary bg-opacity-10 p-3">
                        <i class="bi bi-gem fs-4 text-primary"></i>
                    </div>
                    <div>
                        <div class="fw-bold fs-5">{{ $client->plan->name ?? 'Free' }}</div>
                        <div>
                            @php
                                $statusColors = [
                                    'active'    => 'success',
                                    'trialing'  => 'info',
                                    'suspended' => 'danger',
                                    'cancelled' => 'secondary',
                                ];
                                $statusColor = $statusColors[$client->status] ?? 'secondary';
                            @endphp
                            <span class="badge bg-{{ $statusColor }} bg-opacity-10 text-{{ $statusColor }} border border-{{ $statusColor }} border-opacity-25">
                                {{ ucfirst($client->status) }}
                            </span>
                        </div>
                    </div>
                </div>
                <a href="{{ route('dashboard.billing.index') }}" class="btn btn-outline-primary btn-sm w-100">
                    <i class="bi bi-credit-card me-1"></i> Manage Billing & Plans
                </a>
            </div>
        </div>

        {{-- API Access --}}
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-bottom pt-4 pb-3">
                <h6 class="fw-bold mb-0"><i class="bi bi-key me-2 text-primary"></i>API Access</h6>
            </div>
            <div class="card-body p-4">
                <p class="text-muted small mb-3">
                    Use your API key to integrate Smart Property Management with your applications.
                    Keep it secret — treat it like a password.
                </p>

                <div class="mb-3">
                    <label class="form-label fw-medium">Your API Key</label>
                    <div class="input-group">
                        <input type="password"
                               id="apiKeyField"
                               class="form-control font-monospace"
                               value="{{ $client->api_key }}"
                               readonly>
                        <button type="button"
                                class="btn btn-outline-secondary"
                                id="toggleApiKey"
                                onclick="toggleApiKeyVisibility()"
                                title="Show / Hide">
                            <i class="bi bi-eye" id="apiKeyIcon"></i>
                        </button>
                        <button type="button"
                                class="btn btn-outline-secondary"
                                onclick="copyApiKey()"
                                title="Copy to clipboard">
                            <i class="bi bi-clipboard" id="copyApiIcon"></i>
                        </button>
                    </div>
                    <div class="form-text">Last generated: {{ $client->updated_at->format('M d, Y H:i') }}</div>
                </div>

                <form method="POST"
                      action="{{ route('dashboard.settings.api-keys.regenerate') }}"
                      onsubmit="return confirm('Regenerate your API key? Your current key will stop working immediately and any integrations using it must be updated.')">
                    @csrf
                    <button type="submit" class="btn btn-outline-danger btn-sm w-100">
                        <i class="bi bi-arrow-clockwise me-1"></i> Regenerate API Key
                    </button>
                </form>
            </div>
        </div>

    </div>

</div>

@push('scripts')
<script>
    function toggleApiKeyVisibility() {
        const field = document.getElementById('apiKeyField');
        const icon  = document.getElementById('apiKeyIcon');
        if (field.type === 'password') {
            field.type = 'text';
            icon.classList.replace('bi-eye', 'bi-eye-slash');
        } else {
            field.type = 'password';
            icon.classList.replace('bi-eye-slash', 'bi-eye');
        }
    }

    function copyApiKey() {
        const field = document.getElementById('apiKeyField');
        const icon  = document.getElementById('copyApiIcon');
        navigator.clipboard.writeText(field.value).then(() => {
            icon.classList.replace('bi-clipboard', 'bi-check2');
            setTimeout(() => icon.classList.replace('bi-check2', 'bi-clipboard'), 2000);
        });
    }
</script>
@endpush
@endsection
