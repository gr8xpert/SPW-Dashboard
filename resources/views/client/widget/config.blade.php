@extends('layouts.client')

@section('title', 'Widget Configuration')

@section('page-content')
<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h4 class="mb-1 fw-bold"><i class="bi bi-sliders me-2 text-primary"></i>Widget Configuration</h4>
        <p class="text-muted mb-0">Customize your property search widget appearance and features</p>
    </div>
    <a href="{{ route('dashboard.widget.index') }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i> Back to Widget
    </a>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

<form method="POST" action="{{ route('dashboard.widget.save-config') }}">
    @csrf
    @method('PUT')

    {{-- Feature Toggles --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white border-bottom pt-4 pb-3">
            <h6 class="fw-bold mb-0"><i class="bi bi-toggles me-2 text-primary"></i>Widget Features</h6>
        </div>
        <div class="card-body p-4">
            <p class="text-muted small mb-3">Enable or disable specific features on your property widget.</p>

            <div class="row g-4">
                <div class="col-md-4">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="enableAiSearch" name="enableAiSearch"
                               value="1" @checked(old('enableAiSearch', $config['enableAiSearch'] ?? $client->ai_search_enabled ?? false))>
                        <label class="form-check-label fw-medium" for="enableAiSearch">AI Search</label>
                    </div>
                    <div class="form-text">Enable AI-powered natural language property search</div>
                </div>
                <div class="col-md-4">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="enableMapView" name="enableMapView"
                               value="1" @checked(old('enableMapView', $config['enableMapView'] ?? true))>
                        <label class="form-check-label fw-medium" for="enableMapView">Map View</label>
                    </div>
                    <div class="form-text">Show properties on an interactive map</div>
                </div>
                <div class="col-md-4">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="enableCurrencyConverter" name="enableCurrencyConverter"
                               value="1" @checked(old('enableCurrencyConverter', $config['enableCurrencyConverter'] ?? true))>
                        <label class="form-check-label fw-medium" for="enableCurrencyConverter">Currency Converter</label>
                    </div>
                    <div class="form-text">Allow visitors to view prices in different currencies</div>
                </div>
                <div class="col-md-4">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="enableWishlist" name="enableWishlist"
                               value="1" @checked(old('enableWishlist', $config['enableWishlist'] ?? true))>
                        <label class="form-check-label fw-medium" for="enableWishlist">Wishlist</label>
                    </div>
                    <div class="form-text">Let visitors save properties to a wishlist</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Currency Settings --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white border-bottom pt-4 pb-3">
            <h6 class="fw-bold mb-0"><i class="bi bi-currency-exchange me-2 text-primary"></i>Currency Settings</h6>
        </div>
        <div class="card-body p-4">
            <div class="row g-4">
                <div class="col-md-4">
                    <label for="baseCurrency" class="form-label fw-medium">Base Currency</label>
                    <select class="form-select" id="baseCurrency" name="baseCurrency">
                        @foreach(['EUR', 'GBP', 'USD', 'CHF', 'SEK', 'NOK', 'DKK', 'PLN', 'CZK', 'HUF', 'TRY', 'AED', 'SAR'] as $cur)
                            <option value="{{ $cur }}" @selected(old('baseCurrency', $config['baseCurrency'] ?? 'EUR') === $cur)>{{ $cur }}</option>
                        @endforeach
                    </select>
                    <div class="form-text">The default currency for property prices</div>
                </div>
                <div class="col-md-8">
                    <label class="form-label fw-medium">Available Currencies</label>
                    @php $availCur = old('availableCurrencies', $config['availableCurrencies'] ?? ['EUR', 'GBP', 'USD', 'CHF', 'SEK', 'NOK']); @endphp
                    <div class="d-flex flex-wrap gap-3 mt-1">
                        @foreach(['EUR', 'GBP', 'USD', 'CHF', 'SEK', 'NOK', 'DKK', 'PLN', 'CZK', 'HUF', 'TRY', 'AED', 'SAR'] as $cur)
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="availableCurrencies[]"
                                       value="{{ $cur }}" id="cur_{{ $cur }}"
                                       @checked(in_array($cur, $availCur))>
                                <label class="form-check-label" for="cur_{{ $cur }}">{{ $cur }}</label>
                            </div>
                        @endforeach
                    </div>
                    <div class="form-text mt-2">Select which currencies visitors can switch between</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Branding --}}
    @php $br = $config['branding'] ?? []; @endphp
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white border-bottom pt-4 pb-3">
            <h6 class="fw-bold mb-0"><i class="bi bi-palette me-2 text-primary"></i>Branding</h6>
        </div>
        <div class="card-body p-4">
            <p class="text-muted small mb-3">Customize how your brand appears in inquiry emails and PDF exports.</p>

            <div class="row g-4 mb-4">
                <div class="col-md-6">
                    <label for="companyName" class="form-label fw-medium">Company Name</label>
                    <input type="text" class="form-control" id="companyName" name="companyName"
                           value="{{ old('companyName', $br['companyName'] ?? '') }}" placeholder="My Real Estate">
                    <div class="form-text">Shown in email headers, footers, and PDF</div>
                </div>
                <div class="col-md-6">
                    <label for="websiteUrl" class="form-label fw-medium">Website URL</label>
                    <input type="url" class="form-control" id="websiteUrl" name="websiteUrl"
                           value="{{ old('websiteUrl', $br['websiteUrl'] ?? '') }}" placeholder="https://example.com">
                    <div class="form-text">Link in email and PDF footers</div>
                </div>
            </div>

            <div class="mb-4">
                <label for="logoUrl" class="form-label fw-medium">Logo URL</label>
                <input type="url" class="form-control" id="logoUrl" name="logoUrl"
                       value="{{ old('logoUrl', $br['logoUrl'] ?? '') }}" placeholder="https://example.com/logo.png">
                <div class="form-text">Recommended: 200x60px, PNG or SVG. Appears in email header and PDF.</div>
                <div id="logoPreviewWrap" class="mt-2 p-2 bg-light rounded d-inline-block" style="{{ empty($br['logoUrl']) ? 'display:none' : '' }}">
                    <img id="logoPreview" src="{{ $br['logoUrl'] ?? '' }}" alt="Logo preview" referrerpolicy="no-referrer"
                         style="max-height: 40px; max-width: 200px;"
                         onerror="this.style.display='none';document.getElementById('logoPreviewError').style.display='';"
                         onload="this.style.display='';document.getElementById('logoPreviewError').style.display='none';">
                    <span id="logoPreviewError" class="text-danger small" style="display:none">Failed to load image</span>
                </div>
            </div>

            <div class="row g-4">
                <div class="col-md-4">
                    <label for="primaryColor" class="form-label fw-medium">Primary Color</label>
                    <div class="input-group">
                        <input type="color" class="form-control form-control-color" id="primaryColorPicker"
                               value="{{ old('primaryColor', $br['primaryColor'] ?? '#667eea') }}"
                               oninput="document.getElementById('primaryColor').value=this.value">
                        <input type="text" class="form-control font-monospace" id="primaryColor" name="primaryColor"
                               value="{{ old('primaryColor', $br['primaryColor'] ?? '#667eea') }}" placeholder="#667eea" maxlength="7"
                               oninput="if(/^#[0-9a-fA-F]{6}$/.test(this.value))document.getElementById('primaryColorPicker').value=this.value">
                    </div>
                    <div class="form-text">Buttons, links, prices, PDF accents</div>
                </div>
                <div class="col-md-4">
                    <label for="emailHeaderColor" class="form-label fw-medium">Email Header Color</label>
                    <div class="input-group">
                        <input type="color" class="form-control form-control-color" id="emailHeaderColorPicker"
                               value="{{ old('emailHeaderColor', $br['emailHeaderColor'] ?? $br['primaryColor'] ?? '#333333') }}"
                               oninput="document.getElementById('emailHeaderColor').value=this.value">
                        <input type="text" class="form-control font-monospace" id="emailHeaderColor" name="emailHeaderColor"
                               value="{{ old('emailHeaderColor', $br['emailHeaderColor'] ?? '') }}" placeholder="#333333" maxlength="7"
                               oninput="if(/^#[0-9a-fA-F]{6}$/.test(this.value))document.getElementById('emailHeaderColorPicker').value=this.value">
                    </div>
                    <div class="form-text">Email header background. Falls back to primary color.</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Display Settings --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white border-bottom pt-4 pb-3">
            <h6 class="fw-bold mb-0"><i class="bi bi-grid me-2 text-primary"></i>Display Settings</h6>
        </div>
        <div class="card-body p-4">
            <div class="row g-4">
                <div class="col-md-4">
                    <label for="defaultView" class="form-label fw-medium">Default View</label>
                    <select class="form-select" id="defaultView" name="defaultView">
                        <option value="grid" @selected(old('defaultView', $config['defaultView'] ?? 'grid') === 'grid')>Grid</option>
                        <option value="list" @selected(old('defaultView', $config['defaultView'] ?? 'grid') === 'list')>List</option>
                        <option value="map" @selected(old('defaultView', $config['defaultView'] ?? 'grid') === 'map')>Map</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="perPage" class="form-label fw-medium">Results Per Page</label>
                    <select class="form-select" id="perPage" name="perPage">
                        @foreach([12, 24, 36, 48] as $count)
                            <option value="{{ $count }}" @selected(old('perPage', $config['perPage'] ?? 24) == $count)>{{ $count }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="defaultLanguage" class="form-label fw-medium">Default Language</label>
                    <select class="form-select" id="defaultLanguage" name="defaultLanguage">
                        <option value="en" @selected(old('defaultLanguage', $client->default_language ?? 'en') === 'en')>English</option>
                        <option value="es" @selected(old('defaultLanguage', $client->default_language ?? 'en') === 'es')>Spanish</option>
                        <option value="fr" @selected(old('defaultLanguage', $client->default_language ?? 'en') === 'fr')>French</option>
                        <option value="de" @selected(old('defaultLanguage', $client->default_language ?? 'en') === 'de')>German</option>
                        <option value="it" @selected(old('defaultLanguage', $client->default_language ?? 'en') === 'it')>Italian</option>
                        <option value="nl" @selected(old('defaultLanguage', $client->default_language ?? 'en') === 'nl')>Dutch</option>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <div class="d-flex justify-content-end gap-2">
        <a href="{{ route('dashboard.widget.index') }}" class="btn btn-outline-secondary">Cancel</a>
        <button type="submit" class="btn btn-primary">
            <i class="bi bi-floppy me-1"></i> Save Configuration
        </button>
    </div>
</form>
@endsection

@push('scripts')
<script>
document.getElementById('logoUrl').addEventListener('input', function() {
    var wrap = document.getElementById('logoPreviewWrap');
    var img = document.getElementById('logoPreview');
    var url = this.value.trim();
    if (!url) { wrap.style.display = 'none'; return; }
    wrap.style.display = '';
    img.src = url;
});
</script>
@endpush
