@extends('layouts.admin')

@section('page-title', 'Widget Labels')

@section('page-content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Widget Labels</h1>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#importModal">
                <i class="bi bi-upload"></i> Import
            </button>
            <a href="{{ route('admin.labels.export', ['language' => $language]) }}" class="btn btn-outline-secondary" target="_blank">
                <i class="bi bi-download"></i> Export JSON
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white border-bottom pt-4 pb-3">
            <div class="row g-3 align-items-center">
                <div class="col-md-3">
                    <select class="form-select" id="languageSelector" onchange="changeLanguage(this.value)">
                        @foreach($languages as $code => $name)
                            <option value="{{ $code }}" @selected($language === $code)>{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-5">
                    <form method="GET" class="d-flex gap-2">
                        <input type="hidden" name="language" value="{{ $language }}">
                        <input type="text" class="form-control" name="search" value="{{ $search }}" placeholder="Search labels...">
                        <button type="submit" class="btn btn-outline-primary">
                            <i class="bi bi-search"></i>
                        </button>
                        @if($search)
                            <a href="{{ route('admin.labels.index', ['language' => $language]) }}" class="btn btn-outline-secondary">
                                <i class="bi bi-x"></i>
                            </a>
                        @endif
                    </form>
                </div>
                <div class="col-md-4 text-end">
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addLabelModal">
                        <i class="bi bi-plus-lg"></i> Add Label
                    </button>
                </div>
            </div>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th style="width: 35%">Key</th>
                            <th style="width: 50%">Value</th>
                            <th style="width: 15%" class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($labels as $label)
                            <tr>
                                <td class="font-monospace small text-muted">{{ $label->label_key }}</td>
                                <td>
                                    <form method="POST" action="{{ route('admin.labels.update', $label) }}" class="d-flex gap-2">
                                        @csrf
                                        @method('PUT')
                                        <input type="text" class="form-control form-control-sm" name="label_value"
                                               value="{{ $label->label_value }}" id="label_{{ $label->id }}">
                                        <button type="submit" class="btn btn-sm btn-outline-primary">
                                            <i class="bi bi-check"></i>
                                        </button>
                                    </form>
                                </td>
                                <td class="text-end">
                                    <form method="POST" action="{{ route('admin.labels.destroy', $label) }}"
                                          onsubmit="return confirm('Delete this label?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="text-center py-5 text-muted">
                                    <i class="bi bi-translate display-6 d-block mb-2"></i>
                                    No labels found for {{ $languages[$language] ?? $language }}.
                                    <br>
                                    <a href="#" data-bs-toggle="modal" data-bs-target="#importModal">Import labels</a> to get started.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if($labels->hasPages())
            <div class="card-footer bg-white border-top d-flex justify-content-between align-items-center">
                <small class="text-muted">
                    Showing {{ $labels->firstItem() }} to {{ $labels->lastItem() }} of {{ $labels->total() }} results
                </small>
                {{ $labels->appends(['language' => $language, 'search' => $search])->links('pagination::bootstrap-5') }}
            </div>
        @endif
    </div>

    <div class="mt-3 text-muted small">
        <i class="bi bi-info-circle"></i>
        These are the default labels used across all widget clients. Clients can override individual labels from their dashboard.
    </div>
</div>

{{-- Add Label Modal --}}
<div class="modal fade" id="addLabelModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('admin.labels.store') }}">
                @csrf
                <input type="hidden" name="language" value="{{ $language }}">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Label</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="new_label_key" class="form-label">Label Key</label>
                        <input type="text" class="form-control font-monospace" id="new_label_key" name="label_key"
                               placeholder="e.g., search_button" required>
                        <div class="form-text">Use lowercase with underscores (e.g., listing_type, search_results)</div>
                    </div>
                    <div class="mb-3">
                        <label for="new_label_value" class="form-label">Label Value</label>
                        <input type="text" class="form-control" id="new_label_value" name="label_value"
                               placeholder="e.g., Search" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Label</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Import Modal --}}
<div class="modal fade" id="importModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" action="{{ route('admin.labels.import') }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Import Labels</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="import_language" class="form-label">Language</label>
                        <select class="form-select" id="import_language" name="language">
                            @foreach($languages as $code => $name)
                                <option value="{{ $code }}" @selected($language === $code)>{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="labels_json" class="form-label">Labels JSON</label>
                        <textarea class="form-control font-monospace" id="labels_json" name="labels_json" rows="15"
                                  placeholder='{"search_button": "Search", "listing_type": "Status", ...}'></textarea>
                        <div class="form-text">
                            Paste a JSON object with key-value pairs. Existing labels with the same keys will be updated.
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Import Labels</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function changeLanguage(lang) {
    window.location.href = '{{ route("admin.labels.index") }}?language=' + lang;
}
</script>
@endsection
