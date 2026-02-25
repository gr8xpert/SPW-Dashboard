@extends('layouts.client')

@section('title', $template->name)

@section('page-content')
<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h4 class="mb-1 fw-bold"><i class="bi bi-file-earmark-richtext me-2 text-primary"></i>{{ $template->name }}</h4>
        <p class="text-muted mb-0">
            @php $labels = ['unlayer' => 'Visual Builder', 'html' => 'HTML', 'plain' => 'Plain Text']; @endphp
            {{ $labels[$template->mode] ?? $template->mode }} template
            @if($template->folder) · <span class="text-primary">{{ $template->folder->name }}</span>@endif
        </p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('dashboard.templates.edit', $template) }}" class="btn btn-primary">
            <i class="bi bi-pencil me-1"></i> Edit
        </a>
        <form method="POST" action="{{ route('dashboard.templates.duplicate', $template) }}">
            @csrf
            <button type="submit" class="btn btn-outline-secondary">
                <i class="bi bi-copy me-1"></i> Duplicate
            </button>
        </form>
        <a href="{{ route('dashboard.templates.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Back
        </a>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-transparent fw-semibold py-3">Preview</div>
            <div class="card-body p-0">
                @if($template->html_content)
                    <iframe srcdoc="{{ htmlspecialchars($template->html_content) }}"
                            style="width:100%; height:600px; border:none; border-radius:0 0 8px 8px;"></iframe>
                @elseif($template->plain_text_content)
                    <pre class="p-4 mb-0" style="font-family: inherit; white-space: pre-wrap;">{{ $template->plain_text_content }}</pre>
                @else
                    <div class="text-center py-5 text-muted">
                        <i class="bi bi-file-earmark fs-1 d-block mb-2 opacity-25"></i>
                        No content yet. <a href="{{ route('dashboard.templates.edit', $template) }}">Edit template</a> to add content.
                    </div>
                @endif
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header bg-transparent fw-semibold py-3">Details</div>
            <div class="card-body">
                <table class="table table-sm mb-0">
                    <tr><th class="text-muted fw-normal ps-0">Name</th><td>{{ $template->name }}</td></tr>
                    <tr><th class="text-muted fw-normal ps-0">Mode</th><td>{{ $labels[$template->mode] ?? $template->mode }}</td></tr>
                    <tr><th class="text-muted fw-normal ps-0">Folder</th><td>{{ $template->folder->name ?? 'None' }}</td></tr>
                    <tr><th class="text-muted fw-normal ps-0">Created</th><td>{{ $template->created_at->format('M d, Y') }}</td></tr>
                    <tr><th class="text-muted fw-normal ps-0">Updated</th><td>{{ $template->updated_at->format('M d, Y') }}</td></tr>
                </table>
            </div>
        </div>
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-transparent fw-semibold py-3">Actions</div>
            <div class="card-body d-grid gap-2">
                <a href="{{ route('dashboard.templates.edit', $template) }}" class="btn btn-primary">
                    <i class="bi bi-pencil me-1"></i> Edit Template
                </a>
                <a href="{{ route('dashboard.templates.versions', $template) }}" class="btn btn-outline-secondary">
                    <i class="bi bi-clock-history me-1"></i> Version History
                </a>
                <form method="POST" action="{{ route('dashboard.templates.destroy', $template) }}"
                      onsubmit="return confirm('Delete this template permanently?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-outline-danger w-100">
                        <i class="bi bi-trash me-1"></i> Delete
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
