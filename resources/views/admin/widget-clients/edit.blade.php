@extends('layouts.admin')

@section('page-title', 'Edit Widget Client')

@section('page-content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Edit Widget Client: {{ $client->domain ?: $client->company_name }}</h1>
        <a href="{{ route('admin.widget-clients.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Back to Clients
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="row">
        <div class="col-lg-8">
            <form method="POST" action="{{ route('admin.widget-clients.update', $client) }}">
                @csrf
                @method('PUT')

                {{-- Domain & Identity --}}
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white border-bottom pt-4 pb-3">
                        <h6 class="fw-bold mb-0"><i class="bi bi-globe me-2 text-primary"></i>Domain & Identity</h6>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="company_name" class="form-label">Company Name</label>
                            <input type="text" class="form-control" id="company_name" name="company_name"
                                   value="{{ old('company_name', $client->company_name) }}" required>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="domain" class="form-label">Widget Domain</label>
                                <input type="text" class="form-control" id="domain" name="domain"
                                       value="{{ old('domain', $client->domain) }}" placeholder="example.com">
                            </div>
                            <div class="col-md-6">
                                <label for="owner_email" class="form-label">Owner Email</label>
                                <input type="email" class="form-control" id="owner_email" name="owner_email"
                                       value="{{ old('owner_email', $client->owner_email) }}" placeholder="owner@example.com">
                                <div class="form-text">Receives inquiry notifications from the widget</div>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-8">
                                <label for="api_url" class="form-label">Property API URL</label>
                                <input type="url" class="form-control" id="api_url" name="api_url"
                                       value="{{ old('api_url', $client->api_url) }}" placeholder="https://inmotechplugin.com/resales6">
                                <div class="form-text">The client's CRM / property feed endpoint</div>
                            </div>
                            <div class="col-md-4">
                                <label for="site_name" class="form-label">Site Name</label>
                                <input type="text" class="form-control" id="site_name" name="site_name"
                                       value="{{ old('site_name', $client->site_name) }}" placeholder="Smart Property Widget">
                                <div class="form-text">Display name for the widget</div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="api_key" class="form-label">CRM API Key (Legacy)</label>
                            <input type="text" class="form-control font-monospace" id="api_key" name="api_key"
                                   value="{{ old('api_key', $client->api_key) }}" placeholder="IM-RSO-xxxx...">
                            <div class="form-text">Legacy field - use Resales credentials below for new clients</div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label for="default_language" class="form-label">Default Language</label>
                                <select class="form-select" id="default_language" name="default_language">
                                    <option value="en" @selected(old('default_language', $client->default_language) === 'en')>English</option>
                                    <option value="en_US" @selected(old('default_language', $client->default_language) === 'en_US')>English (US)</option>
                                    <option value="mt" @selected(old('default_language', $client->default_language) === 'mt')>Maltese</option>
                                    <option value="it" @selected(old('default_language', $client->default_language) === 'it')>Italian</option>
                                    <option value="fr" @selected(old('default_language', $client->default_language) === 'fr')>French</option>
                                    <option value="es" @selected(old('default_language', $client->default_language) === 'es')>Spanish</option>
                                </select>
                            </div>
                            <div class="col-md-8">
                                <label class="form-label">Widget Features</label>
                                <div class="d-flex gap-3 mt-2">
                                    @php $features = $client->widget_features ?? ['search', 'detail', 'wishlist']; @endphp
                                    @foreach(['search', 'detail', 'wishlist'] as $feature)
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="widget_features[]"
                                                   value="{{ $feature }}" id="feature_{{ $feature }}"
                                                   @checked(in_array($feature, old('widget_features', $features)))>
                                            <label class="form-check-label" for="feature_{{ $feature }}">{{ ucfirst($feature) }}</label>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Resales Online API Credentials --}}
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white border-bottom pt-4 pb-3">
                        <h6 class="fw-bold mb-0"><i class="bi bi-cloud-arrow-down me-2 text-primary"></i>Resales Online API</h6>
                    </div>
                    <div class="card-body">
                        <p class="text-muted small mb-3">
                            Direct connection to Resales Online WebAPI V6. These credentials bypass Odoo and connect directly to the property feed.
                        </p>

                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label for="resales_client_id" class="form-label">Client ID (p1)</label>
                                <input type="text" class="form-control font-monospace" id="resales_client_id" name="resales_client_id"
                                       value="{{ old('resales_client_id', $client->resales_client_id) }}" placeholder="1003405"
                                       autocomplete="off" data-lpignore="true" data-form-type="other">
                                <div class="form-text">Numeric ID from Resales Online</div>
                            </div>
                            <div class="col-md-8">
                                <label for="resales_api_key" class="form-label">API Key (p2)</label>
                                <input type="text" class="form-control font-monospace" id="resales_api_key" name="resales_api_key"
                                       value="{{ old('resales_api_key', $client->resales_api_key) }}" placeholder="SHA hash from Resales Online"
                                       autocomplete="new-password" data-lpignore="true" data-form-type="other" style="-webkit-text-security: disc;">
                                <div class="form-text">The API key hash - tied to specific IP address</div>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label for="resales_filter_id" class="form-label">Filter ID</label>
                                <input type="text" class="form-control" id="resales_filter_id" name="resales_filter_id"
                                       value="{{ old('resales_filter_id', $client->resales_filter_id ?? '1') }}" placeholder="1">
                                <div class="form-text">p_agency_filterid (usually 1)</div>
                            </div>
                            <div class="col-md-4">
                                <label for="resales_agency_code" class="form-label">Agency Code</label>
                                <input type="text" class="form-control font-monospace" id="resales_agency_code" name="resales_agency_code"
                                       value="{{ old('resales_agency_code', $client->resales_agency_code) }}" placeholder="BIS" maxlength="10">
                                <div class="form-text">Prefix for property refs (e.g., BIS)</div>
                            </div>
                            <div class="col-md-4 d-flex align-items-end">
                                @if($client->resales_client_id && $client->resales_api_key)
                                <button type="button" class="btn btn-outline-primary w-100" onclick="testResalesConnection()">
                                    <i class="bi bi-plug"></i> Test Connection
                                </button>
                                @else
                                <button type="button" class="btn btn-outline-secondary w-100" disabled>
                                    <i class="bi bi-plug"></i> Save first to test
                                </button>
                                @endif
                            </div>
                        </div>

                        <div id="resales-test-result" class="mt-2"></div>
                    </div>
                </div>
                <script>
                function testResalesConnection() {
                    var resultEl = document.getElementById('resales-test-result');
                    resultEl.innerHTML = '<div class="alert alert-info py-2"><i class="bi bi-hourglass-split me-2"></i>Testing connection...</div>';

                    fetch('{{ route("admin.widget-clients.test-resales", $client) }}')
                        .then(function(r) { return r.json(); })
                        .then(function(data) {
                            if (data.success) {
                                resultEl.innerHTML = '<div class="alert alert-success py-2"><i class="bi bi-check-circle me-2"></i>' + data.message + '</div>';
                            } else {
                                resultEl.innerHTML = '<div class="alert alert-danger py-2"><i class="bi bi-x-circle me-2"></i>' + data.message + '</div>';
                            }
                        })
                        .catch(function(e) {
                            resultEl.innerHTML = '<div class="alert alert-danger py-2"><i class="bi bi-x-circle me-2"></i>Connection test failed</div>';
                        });
                }
                </script>

                {{-- Listing Types & Filters --}}
                @php $rs = $client->resales_settings ?? []; @endphp
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white border-bottom pt-4 pb-3">
                        <h6 class="fw-bold mb-0"><i class="bi bi-list-columns me-2 text-primary"></i>Listing Types & Filters</h6>
                    </div>
                    <div class="card-body">
                        <p class="text-muted small mb-3">
                            Configure which listing types are enabled and their filter settings. Each type can have its own filter ID and minimum price.
                        </p>

                        <div class="table-responsive">
                            <table class="table table-sm align-middle">
                                <thead>
                                    <tr>
                                        <th style="width:25%">Listing Type</th>
                                        <th style="width:15%">Enabled</th>
                                        <th style="width:20%">Filter ID</th>
                                        <th style="width:20%">Own Filter</th>
                                        <th style="width:20%">Min Price</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach(['resales' => 'Sales/Resales', 'developments' => 'Developments', 'short_rentals' => 'Short Rentals', 'long_rentals' => 'Long Rentals'] as $key => $label)
                                    @php $lt = $rs[$key] ?? ['enabled' => ($key === 'resales'), 'filter_id' => '1', 'own_filter' => '', 'min_price' => 0]; @endphp
                                    <tr>
                                        <td><strong>{{ $label }}</strong></td>
                                        <td>
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox"
                                                       name="resales_settings[{{ $key }}][enabled]" value="1"
                                                       @checked(old("resales_settings.{$key}.enabled", $lt['enabled'] ?? false))>
                                            </div>
                                        </td>
                                        <td>
                                            <input type="text" class="form-control form-control-sm" style="width:60px"
                                                   name="resales_settings[{{ $key }}][filter_id]"
                                                   value="{{ old("resales_settings.{$key}.filter_id", $lt['filter_id'] ?? '1') }}"
                                                   placeholder="1">
                                        </td>
                                        <td>
                                            <input type="text" class="form-control form-control-sm" style="width:60px"
                                                   name="resales_settings[{{ $key }}][own_filter]"
                                                   value="{{ old("resales_settings.{$key}.own_filter", $lt['own_filter'] ?? '') }}"
                                                   placeholder="4">
                                        </td>
                                        <td>
                                            <input type="number" class="form-control form-control-sm" style="width:100px"
                                                   name="resales_settings[{{ $key }}][min_price]"
                                                   value="{{ old("resales_settings.{$key}.min_price", $lt['min_price'] ?? 0) }}"
                                                   placeholder="0" min="0">
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <div class="form-text">
                            <strong>Filter ID:</strong> The p_agency_filterid parameter for Resales API.<br>
                            <strong>Own Filter:</strong> Additional filter for own properties only.<br>
                            <strong>Min Price:</strong> Minimum price filter for this listing type.
                        </div>
                    </div>
                </div>

                {{-- Display Preferences --}}
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white border-bottom pt-4 pb-3">
                        <h6 class="fw-bold mb-0"><i class="bi bi-sliders2 me-2 text-primary"></i>Display Preferences</h6>
                    </div>
                    <div class="card-body">
                        <p class="text-muted small mb-3">
                            Customize which locations, property types, and features are shown on the widget, and in what order.
                        </p>

                        <div class="d-flex gap-2 flex-wrap">
                            <a href="{{ route('admin.widget-clients.display-preferences', [$client, 'type' => 'location']) }}"
                               class="btn btn-outline-primary btn-sm">
                                <i class="bi bi-geo-alt me-1"></i> Manage Locations
                            </a>
                            <a href="{{ route('admin.widget-clients.display-preferences', [$client, 'type' => 'property_type']) }}"
                               class="btn btn-outline-primary btn-sm">
                                <i class="bi bi-house me-1"></i> Manage Property Types
                            </a>
                            <a href="{{ route('admin.widget-clients.display-preferences', [$client, 'type' => 'feature']) }}"
                               class="btn btn-outline-primary btn-sm">
                                <i class="bi bi-check2-square me-1"></i> Manage Features
                            </a>
                        </div>

                        <div class="mt-3 pt-3 border-top">
                            <h6 class="small text-muted mb-2">Custom Grouping</h6>
                            <p class="form-text mb-2">Create custom groups and organize feed items into them for better organization.</p>

                            <div class="d-flex flex-wrap gap-2 mb-2">
                                <div class="d-flex align-items-center gap-1">
                                    <a href="{{ route('admin.widget-clients.location-grouping.index', $client) }}"
                                       class="btn btn-{{ $client->custom_location_grouping_enabled ? 'primary' : 'outline-secondary' }} btn-sm">
                                        <i class="bi bi-geo-alt me-1"></i> Location Grouping
                                    </a>
                                    @if($client->custom_location_grouping_enabled)
                                        <span class="badge bg-success">On</span>
                                    @endif
                                </div>

                                <div class="d-flex align-items-center gap-1">
                                    <a href="{{ route('admin.widget-clients.property-type-grouping.index', $client) }}"
                                       class="btn btn-{{ $client->custom_property_type_grouping_enabled ? 'primary' : 'outline-secondary' }} btn-sm">
                                        <i class="bi bi-house me-1"></i> Property Type Grouping
                                    </a>
                                    @if($client->custom_property_type_grouping_enabled)
                                        <span class="badge bg-success">On</span>
                                    @endif
                                </div>

                                <div class="d-flex align-items-center gap-1">
                                    <a href="{{ route('admin.widget-clients.feature-grouping.index', $client) }}"
                                       class="btn btn-{{ $client->custom_feature_grouping_enabled ? 'primary' : 'outline-secondary' }} btn-sm">
                                        <i class="bi bi-check2-square me-1"></i> Feature Grouping
                                    </a>
                                    @if($client->custom_feature_grouping_enabled)
                                        <span class="badge bg-success">On</span>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <div class="form-text mt-2">
                            These settings allow you to hide specific items or reorder them to appear in a custom sequence.
                        </div>
                    </div>
                </div>

                {{-- Custom Price Ranges --}}
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white border-bottom pt-4 pb-3">
                        <h6 class="fw-bold mb-0"><i class="bi bi-currency-euro me-2 text-primary"></i>Custom Price Ranges</h6>
                    </div>
                    <div class="card-body">
                        <p class="text-muted small mb-3">
                            Configure custom price dropdown values for each listing type. Leave empty to use default ranges.
                        </p>

                        @php
                            $listingTypes = [
                                'resale' => 'Sales/Resales',
                                'development' => 'Developments',
                                'long_rental' => 'Long Rentals',
                                'short_rental' => 'Short Rentals'
                            ];
                            $priceRanges = $client->widget_config['priceRanges'] ?? [];
                        @endphp

                        <ul class="nav nav-tabs mb-3" role="tablist">
                            @foreach($listingTypes as $key => $label)
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link @if($loop->first) active @endif"
                                            id="price-{{ $key }}-tab"
                                            data-bs-toggle="tab"
                                            data-bs-target="#price-{{ $key }}"
                                            type="button" role="tab">
                                        {{ $label }}
                                        @if(!empty($priceRanges[$key]))
                                            <span class="badge bg-primary ms-1">Custom</span>
                                        @endif
                                    </button>
                                </li>
                            @endforeach
                        </ul>

                        <div class="tab-content">
                            @foreach($listingTypes as $key => $label)
                                <div class="tab-pane fade @if($loop->first) show active @endif"
                                     id="price-{{ $key }}" role="tabpanel">

                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Minimum Price Options</label>
                                            <textarea class="form-control font-monospace"
                                                      name="widget_price_ranges[{{ $key }}][min]"
                                                      rows="3"
                                                      placeholder="150000, 200000, 250000, 300000, 400000, 500000">{{ implode(', ', $priceRanges[$key]['min'] ?? []) }}</textarea>
                                            <div class="form-text">Comma-separated values (ascending order)</div>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Maximum Price Options</label>
                                            <textarea class="form-control font-monospace"
                                                      name="widget_price_ranges[{{ $key }}][max]"
                                                      rows="3"
                                                      placeholder="200000, 300000, 500000, 750000, 1000000, 2000000">{{ implode(', ', $priceRanges[$key]['max'] ?? []) }}</textarea>
                                            <div class="form-text">Comma-separated values (ascending order)</div>
                                        </div>
                                    </div>

                                    @if($key === 'resale' || $key === 'development')
                                        <div class="alert alert-light border py-2 mb-0">
                                            <small><strong>Default:</strong> 50K, 100K, 150K, 200K, 250K, 300K, 400K, 500K, 750K, 1M, 1.5M, 2M, 3M, 5M, 10M, 20M</small>
                                        </div>
                                    @elseif($key === 'long_rental')
                                        <div class="alert alert-light border py-2 mb-0">
                                            <small><strong>Default:</strong> 500, 750, 1000, 1250, 1500, 2000, 2500, 3000, 5000, 10000, 25000</small>
                                        </div>
                                    @elseif($key === 'short_rental')
                                        <div class="alert alert-light border py-2 mb-0">
                                            <small><strong>Default:</strong> 250, 350, 500, 750, 1000, 1250, 1500, 2000, 2500, 3000, 5000, 10000, 25000</small>
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                {{-- Plan & Subscription --}}
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white border-bottom pt-4 pb-3">
                        <h6 class="fw-bold mb-0"><i class="bi bi-gem me-2 text-primary"></i>Plan & Subscription</h6>
                    </div>
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="plan_id" class="form-label">Plan</label>
                                <select class="form-select" id="plan_id" name="plan_id">
                                    @foreach($plans as $plan)
                                        <option value="{{ $plan->id }}" @selected(old('plan_id', $client->plan_id) == $plan->id)>
                                            {{ $plan->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="subscription_status" class="form-label">Subscription Status</label>
                                <select class="form-select" id="subscription_status" name="subscription_status">
                                    <option value="active" @selected(old('subscription_status', $client->subscription_status) === 'active')>Active</option>
                                    <option value="grace" @selected(old('subscription_status', $client->subscription_status) === 'grace')>Grace Period</option>
                                    <option value="expired" @selected(old('subscription_status', $client->subscription_status) === 'expired')>Expired</option>
                                    <option value="manual" @selected(old('subscription_status', $client->subscription_status) === 'manual')>Manual</option>
                                    <option value="internal" @selected(old('subscription_status', $client->subscription_status) === 'internal')>Internal</option>
                                </select>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-3">
                                <label for="billing_cycle" class="form-label">Billing Cycle</label>
                                <select class="form-select" id="billing_cycle" name="billing_cycle">
                                    <option value="monthly" @selected(old('billing_cycle', $client->billing_cycle) === 'monthly')>Monthly</option>
                                    <option value="yearly" @selected(old('billing_cycle', $client->billing_cycle) === 'yearly')>Yearly</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="billing_source" class="form-label">Billing Source</label>
                                <select class="form-select" id="billing_source" name="billing_source">
                                    <option value="manual" @selected(old('billing_source', $client->billing_source) === 'manual')>Manual</option>
                                    <option value="paddle" @selected(old('billing_source', $client->billing_source) === 'paddle')>Paddle</option>
                                    <option value="internal" @selected(old('billing_source', $client->billing_source) === 'internal')>Internal</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="subscription_expires_at" class="form-label">
                                    Expires At
                                    <button type="button" class="btn btn-link btn-sm p-0 ms-1" id="autoCalcExpiry" title="Auto-calculate from created date">
                                        <i class="bi bi-calculator"></i>
                                    </button>
                                </label>
                                <input type="date" class="form-control" id="subscription_expires_at" name="subscription_expires_at"
                                       value="{{ old('subscription_expires_at', $client->subscription_expires_at?->format('Y-m-d')) }}">
                                <div class="form-text text-muted">Grace period (+7 days) calculated automatically</div>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label text-muted">Grace Ends At</label>
                                <input type="text" class="form-control bg-light" id="grace_display" readonly
                                       value="{{ $client->grace_ends_at?->format('M d, Y') ?? 'Auto-calculated on save' }}">
                                <input type="hidden" name="grace_ends_at" id="grace_ends_at"
                                       value="{{ old('grace_ends_at', $client->grace_ends_at?->format('Y-m-d')) }}">
                            </div>
                        </div>
                        <script>
                        (function() {
                            const createdAt = new Date('{{ $client->created_at->format('Y-m-d') }}');
                            const billingCycle = document.getElementById('billing_cycle');
                            const expiresAt = document.getElementById('subscription_expires_at');
                            const graceDisplay = document.getElementById('grace_display');
                            const graceHidden = document.getElementById('grace_ends_at');
                            const autoCalcBtn = document.getElementById('autoCalcExpiry');

                            function addDays(date, days) {
                                const result = new Date(date);
                                result.setDate(result.getDate() + days);
                                return result;
                            }

                            function addMonths(date, months) {
                                const result = new Date(date);
                                result.setMonth(result.getMonth() + months);
                                return result;
                            }

                            function formatDate(date) {
                                return date.toISOString().split('T')[0];
                            }

                            function formatDisplay(date) {
                                return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
                            }

                            function updateGracePeriod() {
                                if (expiresAt.value) {
                                    const expiry = new Date(expiresAt.value);
                                    const grace = addDays(expiry, 7);
                                    graceDisplay.value = formatDisplay(grace);
                                    graceHidden.value = formatDate(grace);
                                } else {
                                    graceDisplay.value = 'Set expiry date first';
                                    graceHidden.value = '';
                                }
                            }

                            function calculateExpiry() {
                                const cycle = billingCycle.value;
                                const months = cycle === 'yearly' ? 12 : 1;
                                // Calculate from current expiry if set, otherwise from created date
                                const baseDate = expiresAt.value ? new Date(expiresAt.value) : createdAt;
                                const newExpiry = addMonths(baseDate, months);
                                expiresAt.value = formatDate(newExpiry);
                                updateGracePeriod();
                            }

                            // Auto-calculate button
                            autoCalcBtn.addEventListener('click', calculateExpiry);

                            // Update grace when expiry changes
                            expiresAt.addEventListener('change', updateGracePeriod);

                            // Initial update
                            updateGracePeriod();
                        })();
                        </script>

                        <div class="d-flex gap-4">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="widget_enabled" name="widget_enabled"
                                       value="1" @checked(old('widget_enabled', $client->widget_enabled))>
                                <label class="form-check-label" for="widget_enabled">Widget Enabled</label>
                            </div>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="ai_search_enabled" name="ai_search_enabled"
                                       value="1" @checked(old('ai_search_enabled', $client->ai_search_enabled))>
                                <label class="form-check-label" for="ai_search_enabled">AI Search</label>
                            </div>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="admin_override" name="admin_override"
                                       value="1" @checked(old('admin_override', $client->admin_override))>
                                <label class="form-check-label" for="admin_override">Admin Override</label>
                            </div>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="is_internal" name="is_internal"
                                       value="1" @checked(old('is_internal', $client->is_internal))>
                                <label class="form-check-label" for="is_internal">Internal</label>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Widget Configuration (pushed to WP plugin via license API) --}}
                @php $wc = $client->widget_config ?? []; $br = $wc['branding'] ?? []; @endphp

                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white border-bottom pt-4 pb-3">
                        <h6 class="fw-bold mb-0"><i class="bi bi-sliders me-2 text-primary"></i>Widget Configuration</h6>
                    </div>
                    <div class="card-body">
                        <p class="text-muted small mb-3">
                            Pushed to the WordPress plugin during license validation. Can be overridden per-site via WP Custom Config.
                        </p>

                        {{-- Feature toggles --}}
                        <div class="d-flex gap-4 mb-4">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="wc_enableMapView" name="wc_enableMapView"
                                       value="1" @checked(old('wc_enableMapView', $wc['enableMapView'] ?? true))>
                                <label class="form-check-label" for="wc_enableMapView">Map View</label>
                            </div>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="wc_enableCurrencyConverter" name="wc_enableCurrencyConverter"
                                       value="1" @checked(old('wc_enableCurrencyConverter', $wc['enableCurrencyConverter'] ?? true))>
                                <label class="form-check-label" for="wc_enableCurrencyConverter">Currency Converter</label>
                            </div>
                        </div>

                        {{-- Currency settings --}}
                        <div class="row mb-4">
                            <div class="col-md-4">
                                <label for="wc_baseCurrency" class="form-label">Base Currency</label>
                                <select class="form-select" id="wc_baseCurrency" name="wc_baseCurrency">
                                    @foreach(['EUR', 'GBP', 'USD', 'CHF', 'SEK', 'NOK', 'DKK', 'PLN', 'CZK', 'HUF', 'TRY', 'AED', 'SAR'] as $cur)
                                        <option value="{{ $cur }}" @selected(old('wc_baseCurrency', $wc['baseCurrency'] ?? 'EUR') === $cur)>{{ $cur }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-8">
                                <label class="form-label">Available Currencies</label>
                                @php $availCur = old('wc_availableCurrencies', $wc['availableCurrencies'] ?? ['EUR', 'GBP', 'USD', 'CHF', 'SEK', 'NOK']); @endphp
                                <div class="d-flex flex-wrap gap-3 mt-1">
                                    @foreach(['EUR', 'GBP', 'USD', 'CHF', 'SEK', 'NOK', 'DKK', 'PLN', 'CZK', 'HUF', 'TRY', 'AED', 'SAR'] as $cur)
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="wc_availableCurrencies[]"
                                                   value="{{ $cur }}" id="cur_{{ $cur }}"
                                                   @checked(in_array($cur, $availCur))>
                                            <label class="form-check-label" for="cur_{{ $cur }}">{{ $cur }}</label>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>

                        {{-- Branding --}}
                        <h6 class="fw-bold mb-3 pt-3 border-top"><i class="bi bi-palette me-2 text-muted"></i>Branding <span class="fw-normal text-muted small">- used in inquiry emails & PDF exports</span></h6>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="wc_companyName" class="form-label">Company Name</label>
                                <input type="text" class="form-control" id="wc_companyName" name="wc_companyName"
                                       value="{{ old('wc_companyName', $br['companyName'] ?? '') }}" placeholder="My Real Estate">
                                <div class="form-text">Shown in email headers, footers, and PDF</div>
                            </div>
                            <div class="col-md-6">
                                <label for="wc_websiteUrl" class="form-label">Website URL</label>
                                <input type="url" class="form-control" id="wc_websiteUrl" name="wc_websiteUrl"
                                       value="{{ old('wc_websiteUrl', $br['websiteUrl'] ?? '') }}" placeholder="https://example.com">
                                <div class="form-text">Link in email and PDF footers</div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="wc_logoUrl" class="form-label">Logo URL</label>
                            <div class="input-group">
                                <input type="url" class="form-control" id="wc_logoUrl" name="wc_logoUrl"
                                       value="{{ old('wc_logoUrl', $br['logoUrl'] ?? '') }}" placeholder="https://example.com/logo.png">
                            </div>
                            <div class="form-text">Recommended: 200x60px, PNG or SVG. Appears in email header and PDF.</div>
                            <div id="logoPreviewWrap" class="mt-2 p-2 bg-light rounded d-inline-block" style="{{ empty($br['logoUrl']) ? 'display:none' : '' }}">
                                <img id="logoPreview" src="{{ $br['logoUrl'] ?? '' }}" alt="Logo preview" referrerpolicy="no-referrer" style="max-height: 40px; max-width: 200px;" onerror="this.style.display='none';document.getElementById('logoPreviewError').style.display='';" onload="this.style.display='';document.getElementById('logoPreviewError').style.display='none';">
                                <span id="logoPreviewError" class="text-danger small" style="display:none">Failed to load image</span>
                            </div>
                            <script>
                            document.getElementById('wc_logoUrl').addEventListener('input', function() {
                                var wrap = document.getElementById('logoPreviewWrap');
                                var img = document.getElementById('logoPreview');
                                var url = this.value.trim();
                                if (!url) { wrap.style.display = 'none'; return; }
                                wrap.style.display = '';
                                img.src = url;
                            });
                            </script>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label for="wc_primaryColor" class="form-label">Primary Color</label>
                                <div class="input-group">
                                    <input type="color" class="form-control form-control-color" id="wc_primaryColorPicker"
                                           value="{{ old('wc_primaryColor', $br['primaryColor'] ?? '#667eea') }}"
                                           oninput="document.getElementById('wc_primaryColor').value=this.value">
                                    <input type="text" class="form-control font-monospace" id="wc_primaryColor" name="wc_primaryColor"
                                           value="{{ old('wc_primaryColor', $br['primaryColor'] ?? '#667eea') }}" placeholder="#667eea" maxlength="7"
                                           oninput="if(/^#[0-9a-fA-F]{6}$/.test(this.value))document.getElementById('wc_primaryColorPicker').value=this.value">
                                </div>
                                <div class="form-text">Buttons, links, prices, PDF accents</div>
                            </div>
                            <div class="col-md-4">
                                <label for="wc_emailHeaderColor" class="form-label">Email Header Color</label>
                                <div class="input-group">
                                    <input type="color" class="form-control form-control-color" id="wc_emailHeaderColorPicker"
                                           value="{{ old('wc_emailHeaderColor', $br['emailHeaderColor'] ?? $br['primaryColor'] ?? '#333333') }}"
                                           oninput="document.getElementById('wc_emailHeaderColor').value=this.value">
                                    <input type="text" class="form-control font-monospace" id="wc_emailHeaderColor" name="wc_emailHeaderColor"
                                           value="{{ old('wc_emailHeaderColor', $br['emailHeaderColor'] ?? '') }}" placeholder="#333333" maxlength="7"
                                           oninput="if(/^#[0-9a-fA-F]{6}$/.test(this.value))document.getElementById('wc_emailHeaderColorPicker').value=this.value">
                                </div>
                                <div class="form-text">Email header background. Falls back to primary color.</div>
                            </div>
                        </div>

                        {{-- Extra JSON overrides --}}
                        <details class="mt-3 pt-3 border-top">
                            <summary class="text-muted small" style="cursor: pointer;">
                                <i class="bi bi-code-slash me-1"></i>Advanced: Extra JSON overrides
                            </summary>
                            <div class="mt-2">
                                <textarea class="form-control font-monospace" id="wc_extraJson" name="wc_extraJson"
                                          rows="5" style="font-size: .82rem;"
                                          placeholder='{"debug": true, "labelsMode": "static"}'>{{ old('wc_extraJson', !empty($wc['_extra']) ? json_encode($wc['_extra'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) : '') }}</textarea>
                                <div id="wc-extra-validation" class="mt-1"></div>
                                <div class="form-text">Additional JSON properties merged into the config. Use for options not covered above.</div>
                            </div>
                        </details>
                        <script>
                        (function() {
                            var ta = document.getElementById('wc_extraJson');
                            var v = document.getElementById('wc-extra-validation');
                            function check() {
                                var val = ta.value.trim();
                                if (!val) { v.innerHTML = ''; return; }
                                try { JSON.parse(val); v.innerHTML = '<span class="text-success small"><i class="bi bi-check-circle"></i> Valid JSON</span>'; }
                                catch(e) { v.innerHTML = '<span class="text-danger small"><i class="bi bi-exclamation-triangle"></i> ' + e.message + '</span>'; }
                            }
                            ta.addEventListener('input', check);
                            check();
                        })();
                        </script>
                    </div>
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-lg"></i> Save Changes
                    </button>
                    <a href="{{ route('admin.widget-clients.index') }}" class="btn btn-outline-secondary">Cancel</a>
                </div>
            </form>
        </div>

        <div class="col-lg-4">
            {{-- Connection Status --}}
            @if($client->domain)
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-header bg-white border-bottom pt-4 pb-3 d-flex justify-content-between align-items-center">
                    <h6 class="fw-bold mb-0"><i class="bi bi-wifi me-2 text-primary"></i>Connection Status</h6>
                    <button class="btn btn-outline-secondary btn-sm" type="button" onclick="checkConnection()">
                        <i class="bi bi-arrow-clockwise"></i>
                    </button>
                </div>
                <div class="card-body" id="connection-status">
                    <div class="text-center py-2">
                        <div class="spinner-border spinner-border-sm text-primary" role="status"></div>
                        <span class="ms-2 text-muted small">Checking...</span>
                    </div>
                </div>
            </div>
            <script>
            function checkConnection() {
                var statusEl = document.getElementById('connection-status');
                statusEl.innerHTML = '<div class="text-center py-2"><div class="spinner-border spinner-border-sm text-primary" role="status"></div><span class="ms-2 text-muted small">Checking...</span></div>';

                fetch('{{ route("admin.widget-clients.check-connection", $client) }}')
                    .then(function(r) { return r.json(); })
                    .then(function(data) {
                        var html = '';

                        // Dashboard config status
                        html += '<div class="d-flex align-items-center gap-2 mb-2">';
                        html += '<i class="bi bi-circle-fill small text-' + (data.config ? 'success' : 'danger') + '"></i>';
                        html += '<span class="small">' + (data.config ? 'Dashboard: Configured' : 'Dashboard: Incomplete') + '</span>';
                        html += '</div>';
                        if (!data.config && data.config_issues) {
                            html += '<div class="text-muted small ms-3 mb-2" style="font-size:.7rem">' + data.config_issues.join(', ') + '</div>';
                        }
                        if (data.config && data.status) {
                            var badge = data.status === 'active' || data.status === 'manual' || data.status === 'internal' ? 'success' : (data.status === 'grace' ? 'warning' : 'danger');
                            html += '<div class="ms-3 mb-2"><span class="badge bg-' + badge + '">' + data.status + '</span>';
                            if (data.override) html += ' <span class="badge bg-info">override</span>';
                            html += '</div>';
                        }

                        // CRM API credentials
                        html += '<div class="d-flex align-items-center gap-2 mb-2">';
                        if (data.api === true) {
                            html += '<i class="bi bi-circle-fill small text-success"></i>';
                            html += '<span class="small">CRM API: Connected</span>';
                        } else if (data.api === false) {
                            html += '<i class="bi bi-circle-fill small text-danger"></i>';
                            html += '<span class="small">CRM API: ' + (data.api_detail || 'Failed') + '</span>';
                        }
                        html += '</div>';

                        statusEl.innerHTML = html;
                    })
                    .catch(function() {
                        statusEl.innerHTML = '<div class="text-danger small"><i class="bi bi-exclamation-triangle me-1"></i>Check failed</div>';
                    });
            }
            document.addEventListener('DOMContentLoaded', checkConnection);
            </script>
            @endif

            {{-- Client Info --}}
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-header bg-white border-bottom pt-4 pb-3">
                    <h6 class="fw-bold mb-0">Client Info</h6>
                </div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-5">ID</dt>
                        <dd class="col-sm-7">{{ $client->id }}</dd>
                        <dt class="col-sm-5">Company</dt>
                        <dd class="col-sm-7">{{ $client->company_name }}</dd>
                        <dt class="col-sm-5">Created</dt>
                        <dd class="col-sm-7">{{ $client->created_at->format('M d, Y') }}</dd>
                        <dt class="col-sm-5">Updated</dt>
                        <dd class="col-sm-7">{{ $client->updated_at->format('M d, Y') }}</dd>
                        <dt class="col-sm-5">License Key</dt>
                        <dd class="col-sm-7">
                            @php $lk = $client->licenseKeys->where('status', 'activated')->first(); @endphp
                            @if($lk)
                                <div class="input-group input-group-sm">
                                    <input type="text" class="form-control form-control-sm font-monospace" id="license-key-copy"
                                           value="{{ $lk->license_key }}" readonly style="font-size: .7rem;">
                                    <button class="btn btn-outline-secondary btn-sm" type="button"
                                            onclick="navigator.clipboard.writeText(document.getElementById('license-key-copy').value)">
                                        <i class="bi bi-clipboard"></i>
                                    </button>
                                </div>
                                <span class="badge bg-success mt-1">Active</span>
                            @else
                                <span class="text-muted">None</span>
                            @endif
                        </dd>
                    </dl>

                    <div class="d-flex gap-2 mt-3 pt-3 border-top">
                        @if($lk)
                            <form method="POST" action="{{ route('admin.widget-clients.revoke-license', $client) }}"
                                  onsubmit="return confirm('Revoke this license key? The widget will stop working.')">
                                @csrf
                                <button type="submit" class="btn btn-outline-danger btn-sm">
                                    <i class="bi bi-x-lg"></i> Revoke
                                </button>
                            </form>
                        @endif
                        <form method="POST" action="{{ route('admin.widget-clients.regenerate-license', $client) }}"
                              onsubmit="return confirm('{{ $lk ? 'This will revoke the current key and generate a new one.' : 'Generate a new license key?' }}')">
                            @csrf
                            <button type="submit" class="btn btn-outline-primary btn-sm">
                                <i class="bi bi-arrow-repeat"></i> {{ $lk ? 'Regenerate' : 'Generate Key' }}
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            {{-- Quick Actions --}}
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-header bg-white border-bottom pt-4 pb-3">
                    <h6 class="fw-bold mb-0">Quick Actions</h6>
                </div>
                <div class="card-body d-grid gap-2">
                    <form method="POST" action="{{ route('admin.widget-clients.toggle-override', $client) }}">
                        @csrf
                        <button type="submit" class="btn btn-outline-{{ $client->admin_override ? 'warning' : 'success' }} w-100 btn-sm">
                            <i class="bi bi-shield-{{ $client->admin_override ? 'x' : 'check' }}"></i>
                            {{ $client->admin_override ? 'Disable Override' : 'Enable Override' }}
                        </button>
                    </form>
                    <form method="POST" action="{{ route('admin.widget-clients.manual-activate', $client) }}">
                        @csrf
                        <button type="submit" class="btn btn-outline-primary w-100 btn-sm">
                            <i class="bi bi-play-circle"></i> Manual Activate
                        </button>
                    </form>
                    <form method="POST" action="{{ route('admin.widget-clients.extend', $client) }}">
                        @csrf
                        <div class="input-group input-group-sm">
                            <select name="period" class="form-select form-select-sm">
                                <option value="1 month">1 Month</option>
                                <option value="3 months">3 Months</option>
                                <option value="6 months">6 Months</option>
                                <option value="1 year">1 Year</option>
                            </select>
                            <button type="submit" class="btn btn-outline-info">
                                <i class="bi bi-calendar-plus"></i> Extend
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            {{-- Danger Zone --}}
            <div class="card border-danger">
                <div class="card-header bg-danger text-white">
                    <h5 class="card-title mb-0">Danger Zone</h5>
                </div>
                <div class="card-body">
                    <p class="text-muted small">Permanently delete this client and all associated data.</p>
                    <form method="POST" action="{{ route('admin.clients.destroy', $client) }}"
                          onsubmit="return confirm('Are you sure you want to delete this client? This action cannot be undone.')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger btn-sm">
                            <i class="bi bi-trash"></i> Delete Client
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
