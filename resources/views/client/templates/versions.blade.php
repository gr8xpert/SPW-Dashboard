@extends('layouts.client')

@section('title', 'Version History: ' . $template->name)

@section('page-content')
<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h4 class="mb-1 fw-bold"><i class="bi bi-clock-history me-2 text-primary"></i>Version History</h4>
        <p class="text-muted mb-0">{{ $template->name }}</p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('dashboard.templates.edit', $template) }}" class="btn btn-primary">
            <i class="bi bi-pencil me-1"></i> Edit Template
        </a>
        <a href="{{ route('dashboard.templates.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> All Templates
        </a>
    </div>
</div>

<div class="row">
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white py-3">
                <div class="d-flex align-items-center justify-content-between">
                    <span class="fw-medium">
                        <i class="bi bi-list-ol me-2 text-muted"></i>
                        {{ $versions->total() }} saved version{{ $versions->total() !== 1 ? 's' : '' }}
                    </span>
                    <small class="text-muted">Most recent first</small>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th width="50">#</th>
                            <th>Saved</th>
                            <th>Saved By</th>
                            <th>Size</th>
                            <th width="130">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($versions as $i => $version)
                            <tr>
                                <td>
                                    <span class="badge bg-light text-dark border fw-normal">
                                        v{{ $versions->total() - $versions->firstItem() - $i + 1 }}
                                    </span>
                                </td>
                                <td>
                                    <div class="fw-medium">{{ $version->created_at->format('M d, Y') }}</div>
                                    <div class="text-muted small">{{ $version->created_at->format('H:i:s') }}</div>
                                    @if($i === 0 && $versions->currentPage() === 1)
                                        <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 small">
                                            Current
                                        </span>
                                    @endif
                                </td>
                                <td>
                                    @if($version->savedBy)
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="rounded-circle bg-primary bg-opacity-10 d-flex align-items-center justify-content-center"
                                                 style="width:30px;height:30px;">
                                                <i class="bi bi-person text-primary small"></i>
                                            </div>
                                            <span class="small">{{ $version->savedBy->name }}</span>
                                        </div>
                                    @else
                                        <span class="text-muted small">—</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="text-muted small">
                                        {{ number_format(strlen($version->html_content ?? '')) }} chars
                                    </span>
                                </td>
                                <td>
                                    <div class="d-flex gap-1">
                                        <button class="btn btn-sm btn-outline-secondary preview-version-btn"
                                                data-html="{{ htmlspecialchars($version->html_content ?? '') }}"
                                                title="Preview">
                                            <i class="bi bi-eye"></i>
                                        </button>
                                        @if(!($i === 0 && $versions->currentPage() === 1))
                                            <form method="POST"
                                                  action="{{ route('dashboard.templates.restore', [$template, $version]) }}"
                                                  onsubmit="return confirm('Restore this version? The current version will be saved before restoring.')">
                                                @csrf
                                                @method('PUT')
                                                <button class="btn btn-sm btn-outline-primary" title="Restore">
                                                    <i class="bi bi-arrow-counterclockwise me-1"></i>Restore
                                                </button>
                                            </form>
                                        @else
                                            <span class="btn btn-sm btn-success disabled">
                                                <i class="bi bi-check-circle me-1"></i>Active
                                            </span>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center py-5 text-muted">
                                    <i class="bi bi-clock-history fs-1 d-block mb-2 opacity-25"></i>
                                    No versions saved yet.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($versions->hasPages())
                <div class="card-footer bg-white border-top-0 d-flex justify-content-between align-items-center">
                    <small class="text-muted">
                        Showing {{ $versions->firstItem() }}–{{ $versions->lastItem() }}
                        of {{ $versions->total() }} versions
                    </small>
                    {{ $versions->links() }}
                </div>
            @endif
        </div>
    </div>

    {{-- Info Panel --}}
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header bg-white fw-medium border-bottom">
                <i class="bi bi-info-circle me-2 text-muted"></i>About This Template
            </div>
            <div class="card-body">
                <dl class="mb-0">
                    <dt class="text-muted small mb-1">Name</dt>
                    <dd class="mb-3">{{ $template->name }}</dd>

                    @if($template->subject)
                    <dt class="text-muted small mb-1">Subject</dt>
                    <dd class="mb-3">{{ $template->subject }}</dd>
                    @endif

                    <dt class="text-muted small mb-1">Editor Type</dt>
                    <dd class="mb-3">{{ ucfirst($template->mode) }}</dd>

                    @if($template->folder)
                    <dt class="text-muted small mb-1">Folder</dt>
                    <dd class="mb-3">{{ $template->folder->name }}</dd>
                    @endif

                    <dt class="text-muted small mb-1">Created</dt>
                    <dd class="mb-0">{{ $template->created_at->format('M d, Y') }}</dd>
                </dl>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white fw-medium border-bottom">
                <i class="bi bi-eye me-2 text-muted"></i>Version Preview
            </div>
            <div class="card-body p-0">
                <div id="versionPreviewPlaceholder"
                     class="text-center text-muted py-5 px-3">
                    <i class="bi bi-eye fs-2 d-block mb-2 opacity-25"></i>
                    <small>Click the <i class="bi bi-eye"></i> icon on a version to preview it here.</small>
                </div>
                <iframe id="versionPreviewFrame"
                        class="d-none"
                        style="width:100%; height:400px; border:none;"></iframe>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    document.querySelectorAll('.preview-version-btn').forEach(btn => {
        btn.addEventListener('click', function () {
            const html = this.getAttribute('data-html');
            document.getElementById('versionPreviewPlaceholder').classList.add('d-none');
            const frame = document.getElementById('versionPreviewFrame');
            frame.classList.remove('d-none');
            frame.srcdoc = html || '<p style="padding:20px;color:#999;">No HTML content for this version.</p>';
        });
    });
</script>
@endpush
@endsection
