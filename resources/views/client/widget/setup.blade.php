@extends('layouts.client')

@section('title', 'Widget Setup')

@section('page-content')
<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h4 class="mb-1 fw-bold"><i class="bi bi-gear-wide-connected me-2 text-primary"></i>Widget Setup</h4>
        <p class="text-muted mb-0">Install the Smart Property Widget on your website</p>
    </div>
</div>

{{-- License Key --}}
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white border-bottom pt-4 pb-3">
        <h6 class="fw-bold mb-0"><i class="bi bi-key me-2 text-primary"></i>License Key</h6>
    </div>
    <div class="card-body p-4">
        <p class="text-muted small mb-3">
            Your unique license key authenticates the widget on your domain. Keep it private.
        </p>
        <div class="mb-3">
            <label class="form-label fw-medium">Your License Key</label>
            <div class="input-group">
                <input type="password"
                       id="licenseKeyField"
                       class="form-control font-monospace"
                       value="{{ $licenseKey ?? '' }}"
                       readonly>
                <button type="button" class="btn btn-outline-secondary" onclick="toggleLicenseKey()" title="Show / Hide">
                    <i class="bi bi-eye" id="licenseKeyIcon"></i>
                </button>
                <button type="button" class="btn btn-outline-secondary" onclick="copyToClipboard('licenseKeyField', 'licenseKeyCopyIcon')" title="Copy">
                    <i class="bi bi-clipboard" id="licenseKeyCopyIcon"></i>
                </button>
            </div>
            <div class="form-text">Authorized domain: <strong>{{ $subscription->domain ?? 'Not set' }}</strong></div>
        </div>
    </div>
</div>

{{-- Embed Code --}}
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white border-bottom pt-4 pb-3">
        <h6 class="fw-bold mb-0"><i class="bi bi-code-slash me-2 text-primary"></i>Embed Code (HTML)</h6>
    </div>
    <div class="card-body p-4">
        <p class="text-muted small mb-3">
            Copy and paste this snippet into the page where you want the widget to appear. Place it just before the closing <code>&lt;/body&gt;</code> tag.
        </p>
        <div class="position-relative">
            <pre class="bg-dark text-light rounded-3 p-3 mb-0" style="font-size: 0.85rem; overflow-x: auto;"><code id="embedCode">&lt;!-- Smart Property Widget --&gt;
&lt;div id="realtysoft-widget"&gt;&lt;/div&gt;
&lt;script src="https://cdn.smartpropertywidget.com/v3/realtysoft.js"
        data-key="{{ $licenseKey ?? 'YOUR_LICENSE_KEY' }}"
        defer&gt;&lt;/script&gt;</code></pre>
            <button type="button"
                    class="btn btn-sm btn-light position-absolute top-0 end-0 m-2"
                    onclick="copyEmbed()"
                    title="Copy embed code">
                <i class="bi bi-clipboard" id="embedCopyIcon"></i> Copy
            </button>
        </div>
    </div>
</div>

{{-- WordPress Plugin --}}
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white border-bottom pt-4 pb-3">
        <h6 class="fw-bold mb-0"><i class="bi bi-wordpress me-2 text-primary"></i>WordPress Plugin</h6>
    </div>
    <div class="card-body p-4">
        <p class="text-muted small mb-3">
            The easiest way to add the widget to your WordPress site. No coding required.
        </p>

        <div class="row g-4">
            <div class="col-md-6">
                <h6 class="fw-semibold mb-3">Installation Steps</h6>
                <ol class="mb-0">
                    <li class="mb-2">
                        <span class="small">Download the plugin ZIP file below.</span>
                    </li>
                    <li class="mb-2">
                        <span class="small">In WordPress admin, go to <strong>Plugins &rarr; Add New &rarr; Upload Plugin</strong>.</span>
                    </li>
                    <li class="mb-2">
                        <span class="small">Upload the ZIP file and click <strong>Install Now</strong>.</span>
                    </li>
                    <li class="mb-2">
                        <span class="small">Activate the plugin, then go to <strong>Settings &rarr; Smart Property Widget</strong>.</span>
                    </li>
                    <li class="mb-2">
                        <span class="small">Paste your license key and save. Use the <code>[realtysoft_widget]</code> shortcode or the Gutenberg block to embed the widget on any page.</span>
                    </li>
                </ol>
            </div>
            <div class="col-md-6">
                <div class="bg-light rounded-3 p-4 text-center">
                    <i class="bi bi-file-earmark-zip fs-1 text-primary d-block mb-3"></i>
                    <h6 class="fw-semibold">RealtySoft Connector Plugin</h6>
                    <p class="text-muted small mb-3">Version {{ $pluginVersion ?? '1.0.0' }} &middot; WordPress 6.0+</p>
                    <a href="{{ route('dashboard.widget.download-plugin') }}" class="btn btn-primary">
                        <i class="bi bi-download me-1"></i> Download Plugin (.zip)
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Customization --}}
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white border-bottom pt-4 pb-3">
        <h6 class="fw-bold mb-0"><i class="bi bi-palette me-2 text-primary"></i>Widget Appearance</h6>
    </div>
    <div class="card-body p-4">
        <form method="POST" action="{{ route('dashboard.widget.update-settings') }}">
            @csrf
            @method('PUT')

            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label fw-medium">Primary Color</label>
                    <div class="input-group">
                        <input type="color"
                               name="primary_color"
                               class="form-control form-control-color"
                               value="{{ old('primary_color', $settings['primary_color'] ?? '#2563EB') }}">
                        <input type="text"
                               class="form-control font-monospace"
                               value="{{ old('primary_color', $settings['primary_color'] ?? '#2563EB') }}"
                               readonly>
                    </div>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-medium">Default View</label>
                    <select name="default_view" class="form-select">
                        <option value="grid" {{ ($settings['default_view'] ?? 'grid') === 'grid' ? 'selected' : '' }}>Grid</option>
                        <option value="list" {{ ($settings['default_view'] ?? 'grid') === 'list' ? 'selected' : '' }}>List</option>
                        <option value="map"  {{ ($settings['default_view'] ?? 'grid') === 'map'  ? 'selected' : '' }}>Map</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-medium">Results Per Page</label>
                    <select name="per_page" class="form-select">
                        @foreach([12, 24, 36, 48] as $count)
                            <option value="{{ $count }}" {{ ($settings['per_page'] ?? 24) == $count ? 'selected' : '' }}>
                                {{ $count }}
                            </option>
                        @endforeach
                    </select>
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
@endsection

@push('scripts')
<script>
function toggleLicenseKey() {
    const field = document.getElementById('licenseKeyField');
    const icon  = document.getElementById('licenseKeyIcon');
    if (field.type === 'password') {
        field.type = 'text';
        icon.classList.replace('bi-eye', 'bi-eye-slash');
    } else {
        field.type = 'password';
        icon.classList.replace('bi-eye-slash', 'bi-eye');
    }
}

function copyToClipboard(fieldId, iconId) {
    const field = document.getElementById(fieldId);
    const icon  = document.getElementById(iconId);
    navigator.clipboard.writeText(field.value).then(() => {
        icon.classList.replace('bi-clipboard', 'bi-check2');
        setTimeout(() => icon.classList.replace('bi-check2', 'bi-clipboard'), 2000);
    });
}

function copyEmbed() {
    const code = document.getElementById('embedCode').textContent;
    const icon = document.getElementById('embedCopyIcon');
    navigator.clipboard.writeText(code).then(() => {
        icon.classList.replace('bi-clipboard', 'bi-check2');
        setTimeout(() => icon.classList.replace('bi-check2', 'bi-clipboard'), 2000);
    });
}
</script>
@endpush
