@extends('layouts.client')

@section('title', 'Templates')

@section('page-content')
<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h4 class="mb-1 fw-bold"><i class="bi bi-file-earmark-richtext me-2 text-primary"></i>Email Templates</h4>
        <p class="text-muted mb-0">Design and manage reusable email templates</p>
    </div>
    <a href="{{ route('dashboard.templates.create') }}" class="btn btn-primary">
        <i class="bi bi-plus-lg me-1"></i> New Template
    </a>
</div>

{{-- Filters --}}
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body py-3">
        <form method="GET" action="{{ route('dashboard.templates.index') }}" class="row g-2 align-items-end">
            <div class="col-md-5">
                <div class="input-group">
                    <span class="input-group-text bg-white"><i class="bi bi-search text-muted"></i></span>
                    <input type="text" name="search" class="form-control border-start-0"
                           placeholder="Search templates..." value="{{ request('search') }}">
                </div>
            </div>
            <div class="col-md-3">
                <select name="folder" class="form-select">
                    <option value="">All Folders</option>
                    @foreach($folders as $folder)
                        <option value="{{ $folder->id }}" {{ request('folder') == $folder->id ? 'selected' : '' }}>
                            {{ $folder->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <select name="type" class="form-select">
                    <option value="">All Types</option>
                    <option value="unlayer" {{ request('type') === 'unlayer' ? 'selected' : '' }}>Visual (Unlayer)</option>
                    <option value="html"    {{ request('type') === 'html'    ? 'selected' : '' }}>HTML Code</option>
                    <option value="plain"   {{ request('type') === 'plain'   ? 'selected' : '' }}>Plain Text</option>
                </select>
            </div>
            <div class="col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-primary flex-fill">Filter</button>
                @if(request()->hasAny(['search', 'folder', 'type']))
                    <a href="{{ route('dashboard.templates.index') }}" class="btn btn-outline-secondary">
                        <i class="bi bi-x-lg"></i>
                    </a>
                @endif
            </div>
        </form>
    </div>
</div>

@if($templates->isEmpty())
    <div class="card border-0 shadow-sm">
        <div class="card-body text-center py-5">
            <i class="bi bi-file-earmark-richtext fs-1 text-muted opacity-25 d-block mb-3"></i>
            <h5 class="text-muted">No templates yet</h5>
            <p class="text-muted">Create your first email template to start building beautiful campaigns.</p>
            <a href="{{ route('dashboard.templates.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-lg me-1"></i> Create Your First Template
            </a>
        </div>
    </div>
@else
    <div class="row g-3">
        @foreach($templates as $template)
            <div class="col-md-6 col-xl-4">
                <div class="card border-0 shadow-sm h-100">
                    {{-- Preview Area --}}
                    <div class="card-img-top bg-light d-flex align-items-center justify-content-center"
                         style="height:160px; overflow:hidden; position:relative;">
                        @if($template->html_content)
                            <div style="transform:scale(0.3); transform-origin:top center; width:333%; height:333%;
                                        pointer-events:none; overflow:hidden;">
                                {!! $template->html_content !!}
                            </div>
                        @else
                            <div class="text-center text-muted">
                                <i class="bi bi-file-earmark-text fs-1 opacity-25"></i>
                                <div class="small mt-1">{{ strtoupper($template->mode) }}</div>
                            </div>
                        @endif
                    </div>

                    <div class="card-body">
                        <div class="d-flex align-items-start justify-content-between mb-2">
                            <h6 class="card-title mb-0 fw-semibold">
                                <a href="{{ route('dashboard.templates.edit', $template) }}"
                                   class="text-decoration-none text-dark">
                                    {{ $template->name }}
                                </a>
                            </h6>
                            @php
                                $typeBadges = [
                                    'unlayer' => ['primary', 'bi-magic'],
                                    'html'    => ['warning', 'bi-code-slash'],
                                    'plain'   => ['secondary', 'bi-file-text'],
                                ];
                                [$color, $icon] = $typeBadges[$template->mode] ?? ['secondary', 'bi-file'];
                            @endphp
                            <span class="badge bg-{{ $color }} bg-opacity-10 text-{{ $color }} border border-{{ $color }} border-opacity-25 ms-2 text-nowrap">
                                <i class="bi {{ $icon }} me-1"></i>{{ ucfirst($template->mode === 'unlayer' ? 'Visual' : $template->mode) }}
                            </span>
                        </div>

                        @if($template->folder)
                            <div class="text-muted small mb-2">
                                <i class="bi bi-folder me-1"></i>{{ $template->folder->name }}
                            </div>
                        @endif

                        @if($template->subject)
                            <p class="text-muted small mb-0 text-truncate" title="{{ $template->subject }}">
                                <i class="bi bi-chat-text me-1"></i>{{ $template->subject }}
                            </p>
                        @endif
                    </div>

                    <div class="card-footer bg-white border-top">
                        <div class="d-flex align-items-center justify-content-between">
                            <small class="text-muted">
                                <i class="bi bi-clock me-1"></i>{{ $template->updated_at->diffForHumans() }}
                            </small>
                            <div class="d-flex gap-1">
                                <a href="{{ route('dashboard.templates.edit', $template) }}"
                                   class="btn btn-sm btn-outline-primary" title="Edit">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <form method="POST" action="{{ route('dashboard.templates.duplicate', $template) }}">
                                    @csrf
                                    <button class="btn btn-sm btn-outline-secondary" title="Duplicate">
                                        <i class="bi bi-copy"></i>
                                    </button>
                                </form>
                                <a href="{{ route('dashboard.templates.versions', $template) }}"
                                   class="btn btn-sm btn-outline-secondary" title="Version History">
                                    <i class="bi bi-clock-history"></i>
                                </a>
                                <form method="POST" action="{{ route('dashboard.templates.destroy', $template) }}"
                                      onsubmit="return confirm('Delete template \'{{ addslashes($template->name) }}\'?')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger" title="Delete">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    @if($templates->hasPages())
        <div class="d-flex justify-content-center mt-4">
            {{ $templates->links() }}
        </div>
    @endif
@endif
@endsection
