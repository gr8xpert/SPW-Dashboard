@extends('layouts.client')

@section('title', 'Image Library')

@section('page-content')
<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h4 class="mb-1 fw-bold"><i class="bi bi-images me-2 text-primary"></i>Image Library</h4>
        <p class="text-muted mb-0">Upload and manage images for use in your email templates</p>
    </div>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#uploadModal">
        <i class="bi bi-cloud-upload me-1"></i> Upload Image
    </button>
</div>

{{-- Quick Upload Bar --}}
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body py-3">
        <form method="POST" action="{{ route('dashboard.images.upload') }}" enctype="multipart/form-data"
              id="quickUploadForm" class="d-flex align-items-center gap-3 flex-wrap">
            @csrf
            <div class="flex-grow-1">
                <div class="input-group">
                    <span class="input-group-text bg-white"><i class="bi bi-image text-muted"></i></span>
                    <input type="file"
                           name="image"
                           id="quickFileInput"
                           class="form-control @error('image') is-invalid @enderror"
                           accept="image/*"
                           required>
                </div>
                @error('image')
                    <div class="text-danger small mt-1">{{ $message }}</div>
                @enderror
            </div>
            <button type="submit" class="btn btn-primary">
                <i class="bi bi-upload me-1"></i> Upload Image
            </button>
        </form>
    </div>
</div>

{{-- Image Grid --}}
@if($images->isEmpty())
    <div class="card border-0 shadow-sm">
        <div class="card-body text-center py-5 text-muted">
            <i class="bi bi-images fs-1 d-block mb-3 opacity-25"></i>
            <p class="mb-2">No images uploaded yet.</p>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#uploadModal">
                <i class="bi bi-cloud-upload me-1"></i> Upload your first image
            </button>
        </div>
    </div>
@else
    <div class="row g-3">
        @foreach($images as $image)
            <div class="col-6 col-md-3">
                <div class="card border-0 shadow-sm h-100">
                    {{-- Thumbnail --}}
                    <div class="position-relative overflow-hidden"
                         style="height: 140px; background: #f8f9fa;">
                        <img src="{{ $image->url }}"
                             alt="{{ $image->original_filename }}"
                             class="w-100 h-100"
                             style="object-fit: cover; object-position: center;">
                    </div>

                    <div class="card-body p-2">
                        <p class="small fw-medium mb-0 text-truncate" title="{{ $image->original_filename }}">
                            {{ $image->original_filename }}
                        </p>
                        <p class="text-muted" style="font-size: 11px; margin-bottom: 8px;">
                            {{ number_format($image->file_size / 1024, 1) }} KB
                            &middot;
                            {{ $image->created_at->format('M d, Y') }}
                        </p>

                        <div class="d-flex gap-1">
                            {{-- Copy URL --}}
                            <button type="button"
                                    class="btn btn-sm btn-outline-secondary flex-grow-1"
                                    title="Copy URL"
                                    onclick="copyImageUrl('{{ $image->url }}', this)">
                                <i class="bi bi-clipboard me-1"></i>
                                <span class="copy-label">Copy URL</span>
                            </button>

                            {{-- Delete --}}
                            <form method="POST"
                                  action="{{ route('dashboard.images.destroy', $image->id) }}"
                                  onsubmit="return confirm('Delete this image? This cannot be undone.')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete image">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    {{-- Pagination --}}
    @if($images->hasPages())
        <div class="d-flex justify-content-between align-items-center mt-4">
            <small class="text-muted">
                Showing {{ $images->firstItem() }}–{{ $images->lastItem() }} of {{ $images->total() }} images
            </small>
            {{ $images->links() }}
        </div>
    @endif
@endif

{{-- Upload Modal --}}
<div class="modal fade" id="uploadModal" tabindex="-1" aria-labelledby="uploadModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('dashboard.images.upload') }}" enctype="multipart/form-data">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="uploadModalLabel">
                        <i class="bi bi-cloud-upload me-2"></i>Upload Image
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info border-0 bg-info bg-opacity-10 mb-3">
                        <i class="bi bi-info-circle me-2"></i>
                        Accepted formats: <strong>JPG, PNG, GIF, WebP, SVG</strong>. Max size: <strong>5MB</strong>.
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-medium">Choose Image <span class="text-danger">*</span></label>
                        <input type="file" name="image" class="form-control" accept="image/*" required>
                    </div>
                    {{-- Preview area --}}
                    <div id="modalImagePreviewWrapper" class="d-none">
                        <label class="form-label fw-medium">Preview</label>
                        <div class="border rounded p-2 text-center bg-light">
                            <img id="modalImagePreview" src="#" alt="Preview"
                                 style="max-height: 200px; max-width: 100%; object-fit: contain;">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-upload me-1"></i> Upload
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
    // Copy image URL to clipboard
    function copyImageUrl(url, btn) {
        navigator.clipboard.writeText(url).then(() => {
            const label = btn.querySelector('.copy-label');
            const icon  = btn.querySelector('i');
            label.textContent  = 'Copied!';
            icon.className     = 'bi bi-check2 me-1';
            btn.classList.replace('btn-outline-secondary', 'btn-outline-success');
            setTimeout(() => {
                label.textContent  = 'Copy URL';
                icon.className     = 'bi bi-clipboard me-1';
                btn.classList.replace('btn-outline-success', 'btn-outline-secondary');
            }, 2000);
        }).catch(() => {
            // Fallback for older browsers
            const textarea = document.createElement('textarea');
            textarea.value = url;
            document.body.appendChild(textarea);
            textarea.select();
            document.execCommand('copy');
            document.body.removeChild(textarea);
        });
    }

    // Show preview in modal when file is chosen
    document.querySelector('#uploadModal input[type=file]').addEventListener('change', function () {
        const file = this.files[0];
        if (!file) return;
        const reader = new FileReader();
        reader.onload = e => {
            document.getElementById('modalImagePreview').src = e.target.result;
            document.getElementById('modalImagePreviewWrapper').classList.remove('d-none');
        };
        reader.readAsDataURL(file);
    });
</script>
@endpush
@endsection
