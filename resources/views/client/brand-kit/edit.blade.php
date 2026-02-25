@extends('layouts.client')

@section('title', 'Brand Kit')

@section('page-content')
<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h4 class="mb-1 fw-bold"><i class="bi bi-palette me-2 text-primary"></i>Brand Kit</h4>
        <p class="text-muted mb-0">Define your brand colors and assets for email templates</p>
    </div>
</div>

<form method="POST" action="{{ route('dashboard.brand-kit.update') }}">
    @csrf
    @method('PUT')

    <div class="row g-4">

        {{-- Left column: form fields --}}
        <div class="col-lg-7">

            {{-- Colors --}}
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white border-bottom-0 pt-4 pb-0">
                    <h6 class="fw-bold mb-0"><i class="bi bi-palette2 me-2 text-primary"></i>Brand Colors</h6>
                </div>
                <div class="card-body p-4">
                    <div class="row g-3">

                        {{-- Primary Color --}}
                        <div class="col-sm-6">
                            <label class="form-label fw-medium">Primary Color</label>
                            <div class="input-group">
                                <input type="color"
                                       id="primaryColorPicker"
                                       name="primary_color"
                                       class="form-control form-control-color @error('primary_color') is-invalid @enderror"
                                       value="{{ old('primary_color', $kit->primary_color ?? '#0d6efd') }}"
                                       title="Pick primary color"
                                       oninput="syncColorInput(this, 'primaryColorHex')">
                                <input type="text"
                                       id="primaryColorHex"
                                       class="form-control"
                                       value="{{ old('primary_color', $kit->primary_color ?? '#0d6efd') }}"
                                       maxlength="7"
                                       placeholder="#0d6efd"
                                       oninput="syncColorPicker(this, 'primaryColorPicker')">
                            </div>
                            @error('primary_color')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Secondary Color --}}
                        <div class="col-sm-6">
                            <label class="form-label fw-medium">Secondary Color</label>
                            <div class="input-group">
                                <input type="color"
                                       id="secondaryColorPicker"
                                       name="secondary_color"
                                       class="form-control form-control-color @error('secondary_color') is-invalid @enderror"
                                       value="{{ old('secondary_color', $kit->secondary_color ?? '#6c757d') }}"
                                       title="Pick secondary color"
                                       oninput="syncColorInput(this, 'secondaryColorHex')">
                                <input type="text"
                                       id="secondaryColorHex"
                                       class="form-control"
                                       value="{{ old('secondary_color', $kit->secondary_color ?? '#6c757d') }}"
                                       maxlength="7"
                                       placeholder="#6c757d"
                                       oninput="syncColorPicker(this, 'secondaryColorPicker')">
                            </div>
                            @error('secondary_color')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                    </div>
                </div>
            </div>

            {{-- Typography & Logo --}}
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white border-bottom-0 pt-4 pb-0">
                    <h6 class="fw-bold mb-0"><i class="bi bi-type me-2 text-primary"></i>Typography & Logo</h6>
                </div>
                <div class="card-body p-4">
                    <div class="row g-3">

                        {{-- Font Family --}}
                        <div class="col-sm-6">
                            <label class="form-label fw-medium">Font Family</label>
                            <select name="font_body"
                                    class="form-select @error('font_body') is-invalid @enderror"
                                    onchange="updatePreview()">
                                @foreach(['Arial' => 'Arial', 'Georgia' => 'Georgia', 'Verdana' => 'Verdana', 'Helvetica' => 'Helvetica', 'Times New Roman' => 'Times New Roman', 'Trebuchet MS' => 'Trebuchet MS', 'system-ui' => 'System UI (Default)'] as $value => $label)
                                    <option value="{{ $value }}"
                                            style="font-family: {{ $value }}"
                                        {{ old('font_body', $kit->font_body ?? 'Arial') === $value ? 'selected' : '' }}>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                            @error('font_body')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Logo URL --}}
                        <div class="col-12">
                            <label class="form-label fw-medium">Logo URL</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-image"></i></span>
                                <input type="url"
                                       name="logo_url"
                                       class="form-control @error('logo_url') is-invalid @enderror"
                                       value="{{ old('logo_url', $kit->logo_url) }}"
                                       placeholder="https://yourdomain.com/logo.png"
                                       oninput="updateLogoPreview(this.value)">
                            </div>
                            <div class="form-text">Enter the full URL to your logo image. Use the <a href="{{ route('dashboard.images.index') }}">Image Library</a> to host your logo.</div>
                            @error('logo_url')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                    </div>
                </div>
            </div>

            {{-- Footer & Social Links --}}
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white border-bottom-0 pt-4 pb-0">
                    <h6 class="fw-bold mb-0"><i class="bi bi-layout-text-sidebar me-2 text-primary"></i>Footer & Social Links</h6>
                </div>
                <div class="card-body p-4">
                    <div class="row g-3">

                        {{-- Footer Text --}}
                        <div class="col-12">
                            <label class="form-label fw-medium">Footer Text</label>
                            <textarea name="footer_html"
                                      rows="3"
                                      class="form-control @error('footer_html') is-invalid @enderror"
                                      placeholder="© 2025 Your Company. All rights reserved.&#10;123 Business Street, City, Country">{{ old('footer_html', $kit->footer_html) }}</textarea>
                            <div class="form-text">Appears at the bottom of your email templates. HTML allowed.</div>
                            @error('footer_html')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Social Links --}}
                        @php
                            $socialLinks = [];
                            if (!empty($kit->social_links)) {
                                $socialLinks = is_array($kit->social_links)
                                    ? $kit->social_links
                                    : json_decode($kit->social_links, true) ?? [];
                            }
                        @endphp

                        <div class="col-sm-6">
                            <label class="form-label fw-medium"><i class="bi bi-facebook me-1 text-primary"></i>Facebook</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-link-45deg"></i></span>
                                <input type="url"
                                       name="social_links[facebook]"
                                       class="form-control @error('social_links.facebook') is-invalid @enderror"
                                       value="{{ old('social_links.facebook', $socialLinks['facebook'] ?? '') }}"
                                       placeholder="https://facebook.com/yourpage">
                            </div>
                            @error('social_links.facebook')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-sm-6">
                            <label class="form-label fw-medium"><i class="bi bi-twitter-x me-1"></i>Twitter / X</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-link-45deg"></i></span>
                                <input type="url"
                                       name="social_links[twitter]"
                                       class="form-control @error('social_links.twitter') is-invalid @enderror"
                                       value="{{ old('social_links.twitter', $socialLinks['twitter'] ?? '') }}"
                                       placeholder="https://twitter.com/yourhandle">
                            </div>
                            @error('social_links.twitter')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-sm-6">
                            <label class="form-label fw-medium"><i class="bi bi-instagram me-1 text-danger"></i>Instagram</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-link-45deg"></i></span>
                                <input type="url"
                                       name="social_links[instagram]"
                                       class="form-control @error('social_links.instagram') is-invalid @enderror"
                                       value="{{ old('social_links.instagram', $socialLinks['instagram'] ?? '') }}"
                                       placeholder="https://instagram.com/yourprofile">
                            </div>
                            @error('social_links.instagram')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-sm-6">
                            <label class="form-label fw-medium"><i class="bi bi-linkedin me-1 text-primary"></i>LinkedIn</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-link-45deg"></i></span>
                                <input type="url"
                                       name="social_links[linkedin]"
                                       class="form-control @error('social_links.linkedin') is-invalid @enderror"
                                       value="{{ old('social_links.linkedin', $socialLinks['linkedin'] ?? '') }}"
                                       placeholder="https://linkedin.com/company/yourcompany">
                            </div>
                            @error('social_links.linkedin')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                    </div>
                </div>
            </div>

            <div class="d-flex justify-content-end">
                <button type="submit" class="btn btn-primary px-4">
                    <i class="bi bi-floppy me-1"></i> Save Brand Kit
                </button>
            </div>

        </div>

        {{-- Right column: live preview --}}
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm sticky-top" style="top: 80px;">
                <div class="card-header bg-white border-bottom pt-4 pb-3">
                    <h6 class="fw-bold mb-0"><i class="bi bi-eye me-2 text-primary"></i>Live Preview</h6>
                </div>
                <div class="card-body p-0">
                    {{-- Email preview mock --}}
                    <div id="emailPreview" style="font-family: {{ old('font_body', $kit->font_body ?? 'Arial') }}, sans-serif; background: #f8f9fa; padding: 0;">

                        {{-- Header --}}
                        <div id="previewHeader"
                             style="background: {{ old('primary_color', $kit->primary_color ?? '#0d6efd') }}; padding: 24px; text-align: center;">
                            @if(!empty($kit->logo_url))
                                <img id="previewLogo" src="{{ $kit->logo_url }}" alt="Logo"
                                     style="max-height: 50px; max-width: 180px; object-fit: contain;">
                            @else
                                <div id="previewLogo" style="color: #fff; font-size: 22px; font-weight: bold; letter-spacing: 1px;">
                                    Your Logo
                                </div>
                            @endif
                        </div>

                        {{-- Body --}}
                        <div style="padding: 28px 24px; background: #ffffff;">
                            <h2 id="previewHeading"
                                style="color: {{ old('primary_color', $kit->primary_color ?? '#0d6efd') }}; margin: 0 0 12px; font-size: 20px;">
                                Email Heading
                            </h2>
                            <p style="color: #555; line-height: 1.6; margin: 0 0 16px;">
                                This is a preview of how your brand colors and typography will appear in email templates sent to your subscribers.
                            </p>
                            <a id="previewButton"
                               href="#"
                               onclick="return false;"
                               style="display: inline-block; background: {{ old('primary_color', $kit->primary_color ?? '#0d6efd') }}; color: #fff; padding: 10px 22px; border-radius: 5px; text-decoration: none; font-weight: 600;">
                                Call to Action
                            </a>
                        </div>

                        {{-- Secondary section --}}
                        <div id="previewSecondaryBar"
                             style="background: {{ old('secondary_color', $kit->secondary_color ?? '#6c757d') }}; padding: 6px 24px;">
                        </div>

                        {{-- Footer --}}
                        <div style="background: #f1f3f5; padding: 16px 24px; text-align: center;">
                            <div style="display: flex; justify-content: center; gap: 12px; margin-bottom: 10px; font-size: 18px;">
                                <i class="bi bi-facebook" style="color: #1877f2;"></i>
                                <i class="bi bi-twitter-x" style="color: #000;"></i>
                                <i class="bi bi-instagram" style="color: #e1306c;"></i>
                                <i class="bi bi-linkedin" style="color: #0077b5;"></i>
                            </div>
                            <p id="previewFooter" style="color: #888; font-size: 12px; margin: 0; line-height: 1.5;">
                                {{ $kit->footer_html ?? '© ' . date('Y') . ' Your Company. All rights reserved.' }}
                            </p>
                        </div>

                    </div>
                </div>

                {{-- Color swatches legend --}}
                <div class="card-footer bg-white border-top d-flex gap-3 py-3 px-4">
                    <div class="d-flex align-items-center gap-2">
                        <div id="swatchPrimary" class="rounded"
                             style="width:24px; height:24px; background:{{ old('primary_color', $kit->primary_color ?? '#0d6efd') }}; border:1px solid rgba(0,0,0,.1);"></div>
                        <small class="text-muted">Primary</small>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <div id="swatchSecondary" class="rounded"
                             style="width:24px; height:24px; background:{{ old('secondary_color', $kit->secondary_color ?? '#6c757d') }}; border:1px solid rgba(0,0,0,.1);"></div>
                        <small class="text-muted">Secondary</small>
                    </div>
                </div>
            </div>
        </div>

    </div>
</form>

@push('scripts')
<script>
    function syncColorInput(picker, hexId) {
        document.getElementById(hexId).value = picker.value;
        updatePreview();
    }

    function syncColorPicker(hexInput, pickerId) {
        const val = hexInput.value;
        if (/^#[0-9A-Fa-f]{6}$/.test(val)) {
            document.getElementById(pickerId).value = val;
            updatePreview();
        }
    }

    function updatePreview() {
        const primary   = document.getElementById('primaryColorPicker').value;
        const secondary = document.getElementById('secondaryColorPicker').value;
        const font      = document.querySelector('[name="font_body"]').value;

        // Update preview elements
        document.getElementById('previewHeader').style.background        = primary;
        document.getElementById('previewHeading').style.color            = primary;
        document.getElementById('previewButton').style.background        = primary;
        document.getElementById('previewSecondaryBar').style.background  = secondary;
        document.getElementById('emailPreview').style.fontFamily         = font + ', sans-serif';

        // Update swatches
        document.getElementById('swatchPrimary').style.background   = primary;
        document.getElementById('swatchSecondary').style.background = secondary;
    }

    function updateLogoPreview(url) {
        const container = document.getElementById('previewLogo');
        if (url) {
            container.outerHTML = '<img id="previewLogo" src="' + url + '" alt="Logo" style="max-height:50px; max-width:180px; object-fit:contain;" onerror="this.style.display=\'none\'">';
        }
    }
</script>
@endpush
@endsection
