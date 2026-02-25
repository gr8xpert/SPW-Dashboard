@extends('layouts.client')

@section('title', 'Getting Started')

@section('page-content')
<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="text-center mb-4">
            <h4 class="fw-bold"><i class="bi bi-rocket-takeoff me-2 text-primary"></i>Welcome to Smart Property Widget</h4>
            <p class="text-muted">Let's get your widget set up in a few easy steps</p>
        </div>

        {{-- Step Indicators --}}
        <div class="d-flex justify-content-center mb-4">
            @php $currentStep = $step ?? 1; @endphp
            @foreach(['Domain', 'License', 'Install', 'Customize', 'Go Live'] as $i => $label)
                @php $stepNum = $i + 1; @endphp
                <div class="d-flex align-items-center">
                    <div class="text-center">
                        <div class="rounded-circle d-flex align-items-center justify-content-center mx-auto mb-1
                            {{ $stepNum < $currentStep ? 'bg-success text-white' : ($stepNum === $currentStep ? 'bg-primary text-white' : 'bg-light text-muted border') }}"
                            style="width: 36px; height: 36px;">
                            @if($stepNum < $currentStep)
                                <i class="bi bi-check-lg"></i>
                            @else
                                {{ $stepNum }}
                            @endif
                        </div>
                        <small class="d-block {{ $stepNum === $currentStep ? 'fw-semibold text-primary' : 'text-muted' }}" style="font-size: .7rem;">{{ $label }}</small>
                    </div>
                    @if($stepNum < 5)
                        <div class="mx-2 {{ $stepNum < $currentStep ? 'bg-success' : 'bg-light' }}" style="height: 2px; width: 40px; margin-top: -12px;"></div>
                    @endif
                </div>
            @endforeach
        </div>

        {{-- Step 1: Domain --}}
        <div id="step-1" class="{{ $currentStep === 1 ? '' : 'd-none' }}">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom pt-4 pb-3">
                    <h6 class="fw-bold mb-0"><i class="bi bi-globe me-2 text-primary"></i>Step 1: Register Your Domain</h6>
                </div>
                <div class="card-body p-4">
                    <p class="text-muted small mb-3">
                        Enter the domain where the widget will be installed. The widget will only work on this domain.
                    </p>
                    <form method="POST" action="{{ route('dashboard.onboarding.save-step') }}">
                        @csrf
                        <input type="hidden" name="step" value="1">
                        <div class="mb-3">
                            <label class="form-label fw-medium">Website Domain <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-globe"></i></span>
                                <input type="text"
                                       name="domain"
                                       class="form-control @error('domain') is-invalid @enderror"
                                       value="{{ old('domain', $onboarding['domain'] ?? '') }}"
                                       placeholder="example.com"
                                       required>
                            </div>
                            <div class="form-text">Do not include http:// or www.</div>
                            @error('domain')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="d-flex justify-content-end">
                            <button type="submit" class="btn btn-primary">
                                Continue <i class="bi bi-arrow-right ms-1"></i>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- Step 2: License --}}
        <div id="step-2" class="{{ $currentStep === 2 ? '' : 'd-none' }}">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom pt-4 pb-3">
                    <h6 class="fw-bold mb-0"><i class="bi bi-key me-2 text-primary"></i>Step 2: Your License Key</h6>
                </div>
                <div class="card-body p-4">
                    <p class="text-muted small mb-3">
                        Your license key has been generated. You will need it in the next step.
                    </p>
                    <div class="bg-light rounded-3 p-3 mb-3 font-monospace text-center fw-bold fs-5" id="onboardingLicenseKey">
                        {{ $licenseKey ?? 'XXXX-XXXX-XXXX-XXXX' }}
                    </div>
                    <div class="text-center mb-3">
                        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="navigator.clipboard.writeText(document.getElementById('onboardingLicenseKey').textContent.trim())">
                            <i class="bi bi-clipboard me-1"></i> Copy to Clipboard
                        </button>
                    </div>
                    <form method="POST" action="{{ route('dashboard.onboarding.save-step') }}">
                        @csrf
                        <input type="hidden" name="step" value="2">
                        <div class="d-flex justify-content-between">
                            <a href="{{ route('dashboard.onboarding.index', ['step' => 1]) }}" class="btn btn-outline-secondary">
                                <i class="bi bi-arrow-left me-1"></i> Back
                            </a>
                            <button type="submit" class="btn btn-primary">
                                Continue <i class="bi bi-arrow-right ms-1"></i>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- Step 3: Install --}}
        <div id="step-3" class="{{ $currentStep === 3 ? '' : 'd-none' }}">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom pt-4 pb-3">
                    <h6 class="fw-bold mb-0"><i class="bi bi-code-slash me-2 text-primary"></i>Step 3: Install the Widget</h6>
                </div>
                <div class="card-body p-4">
                    <p class="text-muted small mb-3">
                        Choose your installation method below.
                    </p>

                    <ul class="nav nav-tabs mb-3" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-html">HTML Embed</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-wp">WordPress</button>
                        </li>
                    </ul>

                    <div class="tab-content">
                        <div class="tab-pane fade show active" id="tab-html">
                            <pre class="bg-dark text-light rounded-3 p-3" style="font-size: .85rem;"><code>&lt;div id="realtysoft-widget"&gt;&lt;/div&gt;
&lt;script src="https://cdn.smartpropertywidget.com/v3/realtysoft.js"
        data-key="{{ $licenseKey ?? 'YOUR_KEY' }}"
        defer&gt;&lt;/script&gt;</code></pre>
                            <p class="text-muted small">Paste this before the closing <code>&lt;/body&gt;</code> tag on your page.</p>
                        </div>
                        <div class="tab-pane fade" id="tab-wp">
                            <ol class="small">
                                <li class="mb-2">Download and install the <strong>RealtySoft Connector</strong> plugin.</li>
                                <li class="mb-2">Go to <strong>Settings &rarr; Smart Property Widget</strong> and enter your license key.</li>
                                <li class="mb-2">Add the <code>[realtysoft_widget]</code> shortcode to any page.</li>
                            </ol>
                            <a href="{{ route('dashboard.widget.download-plugin') }}" class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-download me-1"></i> Download Plugin
                            </a>
                        </div>
                    </div>

                    <hr>

                    <form method="POST" action="{{ route('dashboard.onboarding.save-step') }}">
                        @csrf
                        <input type="hidden" name="step" value="3">
                        <div class="d-flex justify-content-between">
                            <a href="{{ route('dashboard.onboarding.index', ['step' => 2]) }}" class="btn btn-outline-secondary">
                                <i class="bi bi-arrow-left me-1"></i> Back
                            </a>
                            <button type="submit" class="btn btn-primary">
                                Continue <i class="bi bi-arrow-right ms-1"></i>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- Step 4: Customize --}}
        <div id="step-4" class="{{ $currentStep === 4 ? '' : 'd-none' }}">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom pt-4 pb-3">
                    <h6 class="fw-bold mb-0"><i class="bi bi-palette me-2 text-primary"></i>Step 4: Customize Appearance</h6>
                </div>
                <div class="card-body p-4">
                    <form method="POST" action="{{ route('dashboard.onboarding.save-step') }}">
                        @csrf
                        <input type="hidden" name="step" value="4">

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-medium">Primary Color</label>
                                <input type="color"
                                       name="primary_color"
                                       class="form-control form-control-color w-100"
                                       value="{{ old('primary_color', '#2563EB') }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-medium">Default View</label>
                                <select name="default_view" class="form-select">
                                    <option value="grid">Grid</option>
                                    <option value="list">List</option>
                                    <option value="map">Map</option>
                                </select>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-medium">Company Logo URL <span class="text-muted fw-normal">(optional)</span></label>
                            <input type="url"
                                   name="logo_url"
                                   class="form-control"
                                   value="{{ old('logo_url') }}"
                                   placeholder="https://example.com/logo.png">
                        </div>

                        <div class="d-flex justify-content-between">
                            <a href="{{ route('dashboard.onboarding.index', ['step' => 3]) }}" class="btn btn-outline-secondary">
                                <i class="bi bi-arrow-left me-1"></i> Back
                            </a>
                            <button type="submit" class="btn btn-primary">
                                Continue <i class="bi bi-arrow-right ms-1"></i>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- Step 5: Go Live --}}
        <div id="step-5" class="{{ $currentStep === 5 ? '' : 'd-none' }}">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-5 text-center">
                    <div class="rounded-circle bg-success bg-opacity-10 d-inline-flex align-items-center justify-content-center mb-3" style="width: 80px; height: 80px;">
                        <i class="bi bi-check-lg fs-1 text-success"></i>
                    </div>
                    <h5 class="fw-bold mb-2">You're All Set!</h5>
                    <p class="text-muted mb-4">
                        Your Smart Property Widget is configured and ready to go. Visit your website to see it in action.
                    </p>

                    <div class="d-flex justify-content-center gap-3">
                        <form method="POST" action="{{ route('dashboard.onboarding.complete') }}">
                            @csrf
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-house-door me-1"></i> Go to Dashboard
                            </button>
                        </form>
                        <a href="{{ route('dashboard.widget.setup') }}" class="btn btn-outline-secondary">
                            <i class="bi bi-gear me-1"></i> Advanced Settings
                        </a>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>
@endsection
