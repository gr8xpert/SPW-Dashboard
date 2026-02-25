@extends('layouts.client')

@section('title', 'Edit: ' . $template->name)

@section('page-content')
<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h4 class="mb-1 fw-bold"><i class="bi bi-pencil-square me-2 text-primary"></i>Edit Template</h4>
        <p class="text-muted mb-0">{{ $template->name }}</p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('dashboard.templates.versions', $template) }}" class="btn btn-outline-secondary">
            <i class="bi bi-clock-history me-1"></i> History
        </a>
        <a href="{{ route('dashboard.templates.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Back
        </a>
    </div>
</div>

<form method="POST" action="{{ route('dashboard.templates.update', $template) }}" id="templateForm">
    @csrf
    @method('PUT')
    <input type="hidden" name="html_content" id="html_body_input" value="{{ $template->html_content }}">
    <input type="hidden" name="json_design"  id="json_body_input"
           value="{{ $template->json_design ? json_encode($template->json_design) : '' }}">

    <div class="row g-4">
        {{-- Metadata Sidebar --}}
        <div class="col-lg-3">
            <div class="card border-0 shadow-sm sticky-top" style="top:20px;">
                <div class="card-header bg-white fw-medium border-bottom">
                    Template Details
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label fw-medium small">Template Name <span class="text-danger">*</span></label>
                        <input type="text" name="name"
                               class="form-control @error('name') is-invalid @enderror"
                               value="{{ old('name', $template->name) }}" required>
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-medium small">Subject Line</label>
                        <input type="text" name="subject"
                               class="form-control @error('subject') is-invalid @enderror"
                               value="{{ old('subject', $template->subject) }}"
                               placeholder="Your email subject...">
                        @error('subject')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    @if($folders->count())
                    <div class="mb-3">
                        <label class="form-label fw-medium small">Folder</label>
                        <select name="folder_id" class="form-select">
                            <option value="">No Folder</option>
                            @foreach($folders as $folder)
                                <option value="{{ $folder->id }}"
                                    {{ old('folder_id', $template->folder_id) == $folder->id ? 'selected' : '' }}>
                                    {{ $folder->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    @endif

                    <div class="mb-3">
                        <label class="form-label fw-medium small">Editor Type</label>
                        @php
                            $typeLabels = ['unlayer' => 'Visual Builder', 'html' => 'HTML Code', 'plain' => 'Plain Text'];
                            $typeColors = ['unlayer' => 'primary', 'html' => 'warning', 'plain' => 'secondary'];
                            $tc = $typeColors[$template->mode] ?? 'secondary';
                        @endphp
                        <div class="badge bg-{{ $tc }} bg-opacity-10 text-{{ $tc }} border border-{{ $tc }} border-opacity-25 w-100 py-2">
                            {{ $typeLabels[$template->mode] ?? $template->mode }}
                        </div>
                    </div>

                    <hr>

                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save me-1"></i> Save Template
                        </button>
                        <button type="button" class="btn btn-outline-secondary btn-sm"
                                onclick="document.getElementById('duplicateTemplateForm').submit()">
                            <i class="bi bi-copy me-1"></i> Duplicate
                        </button>
                    </div>

                    <hr>

                    <button type="button" class="btn btn-outline-danger btn-sm w-100"
                            onclick="if(confirm('Delete this template permanently?')) document.getElementById('deleteTemplateForm').submit()">
                        <i class="bi bi-trash me-1"></i> Delete Template
                    </button>
                </div>
            </div>
        </div>

        {{-- Editor Area --}}
        <div class="col-lg-9">
            @if($template->mode === 'unlayer')
                <div class="card border-0 shadow-sm mb-3">
                    <div class="card-header bg-white d-flex align-items-center justify-content-between py-2">
                        <span class="fw-medium">
                            <i class="bi bi-magic me-2 text-primary"></i>Visual Builder
                        </span>
                        <button type="button" class="btn btn-sm btn-outline-secondary"
                                data-bs-toggle="modal" data-bs-target="#aiModal">
                            <i class="bi bi-stars me-1 text-warning"></i> AI Generate
                        </button>
                    </div>
                </div>
                <div id="unlayer-container"
                     style="height:650px; border:1px solid #dee2e6; border-radius:8px; overflow:hidden;">
                </div>

            @elseif($template->mode === 'html')
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white d-flex align-items-center justify-content-between py-2">
                        <span class="fw-medium">
                            <i class="bi bi-code-slash me-2 text-warning"></i>HTML Code Editor
                        </span>
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-sm btn-outline-secondary"
                                    data-bs-toggle="modal" data-bs-target="#aiModal">
                                <i class="bi bi-stars me-1 text-warning"></i> AI Generate
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-secondary" id="previewBtn">
                                <i class="bi bi-eye me-1"></i> Preview
                            </button>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <textarea name="html_content" id="html_body_textarea" rows="30"
                                  class="form-control border-0 rounded-0 font-monospace"
                                  style="resize:vertical; font-size:13px; min-height:500px;">{{ old('html_content', $template->html_content) }}</textarea>
                    </div>
                </div>
                {{-- Preview Panel --}}
                <div id="previewPanel" class="card border-0 shadow-sm mt-3 d-none">
                    <div class="card-header bg-white d-flex align-items-center justify-content-between py-2">
                        <span class="fw-medium"><i class="bi bi-eye me-2"></i>Preview</span>
                        <button class="btn btn-sm btn-outline-secondary" onclick="closePreview()">
                            <i class="bi bi-x-lg"></i>
                        </button>
                    </div>
                    <div class="card-body p-0">
                        <iframe id="previewFrame" style="width:100%; height:500px; border:none;"></iframe>
                    </div>
                </div>

            @elseif($template->mode === 'plain')
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white py-2">
                        <span class="fw-medium">
                            <i class="bi bi-file-text me-2 text-secondary"></i>Plain Text Editor
                        </span>
                    </div>
                    <div class="card-body p-0">
                        <textarea name="plain_text_content" rows="30"
                                  class="form-control border-0 rounded-0"
                                  style="resize:vertical; font-size:14px; min-height:500px;">{{ old('plain_text_content', $template->plain_text_content) }}</textarea>
                    </div>
                </div>
            @endif
        </div>
    </div>
</form>

{{-- Standalone forms (outside main form to avoid nesting) --}}
<form id="duplicateTemplateForm" method="POST"
      action="{{ route('dashboard.templates.duplicate', $template) }}" class="d-none">
    @csrf
</form>
<form id="deleteTemplateForm" method="POST"
      action="{{ route('dashboard.templates.destroy', $template) }}" class="d-none">
    @csrf
    @method('DELETE')
</form>

{{-- AI Generate Modal --}}
<div class="modal fade" id="aiModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bi bi-stars me-2 text-warning"></i>AI Email Generator
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-warning border-0 bg-warning bg-opacity-10 small">
                    <i class="bi bi-exclamation-triangle me-1"></i>
                    This will replace your current template content.
                </div>
                <div class="mb-3">
                    <label class="form-label fw-medium">Describe your email <span class="text-danger">*</span></label>
                    <textarea id="aiPrompt" class="form-control" rows="3"
                              placeholder="e.g. A promotional email for a 50% off sale with urgency..."></textarea>
                </div>
                <div class="row g-3">
                    <div class="col-sm-6">
                        <label class="form-label fw-medium">Tone</label>
                        <select id="aiTone" class="form-select">
                            <option value="professional">Professional</option>
                            <option value="friendly">Friendly</option>
                            <option value="casual">Casual</option>
                            <option value="formal">Formal</option>
                            <option value="urgent">Urgent</option>
                        </select>
                    </div>
                    <div class="col-sm-6">
                        <label class="form-label fw-medium">Industry</label>
                        <select id="aiIndustry" class="form-select">
                            <option value="">General</option>
                            <option value="SaaS / Technology">SaaS / Technology</option>
                            <option value="E-commerce">E-commerce</option>
                            <option value="Healthcare">Healthcare</option>
                            <option value="Finance">Finance</option>
                            <option value="Education">Education</option>
                            <option value="Real Estate">Real Estate</option>
                        </select>
                    </div>
                </div>
                <div id="aiError" class="alert alert-danger mt-3 d-none"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-warning" id="aiGenerateBtn">
                    <i class="bi bi-stars me-1"></i> Generate
                </button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
@if($template->mode === 'unlayer')
<script src="https://editor.unlayer.com/embed.js"></script>
<script>
    unlayer.init({
        id: 'unlayer-container',
        projectId: 1,
        displayMode: 'email',
    });

    @if($template->json_design)
        unlayer.loadDesign(@json($template->json_design));
    @elseif($template->html_content)
        unlayer.loadDesign({
            body: { rows: [], values: {} },
        });
    @endif

    document.getElementById('templateForm').addEventListener('submit', function (e) {
        e.preventDefault();
        unlayer.exportHtml(function (data) {
            document.getElementById('html_body_input').value = data.html;
            document.getElementById('json_body_input').value = JSON.stringify(data.design);
            document.getElementById('templateForm').submit();
        });
    });
</script>
@endif

@if($template->mode === 'html')
<script>
    // Sync html textarea to hidden input on submit
    document.getElementById('templateForm').addEventListener('submit', function () {
        document.getElementById('html_body_input').value =
            document.getElementById('html_body_textarea').value;
    });

    // Preview
    document.getElementById('previewBtn').addEventListener('click', function () {
        const html = document.getElementById('html_body_textarea').value;
        const panel = document.getElementById('previewPanel');
        const frame = document.getElementById('previewFrame');
        panel.classList.remove('d-none');
        frame.srcdoc = html;
        panel.scrollIntoView({ behavior: 'smooth' });
    });

    function closePreview() {
        document.getElementById('previewPanel').classList.add('d-none');
    }
</script>
@endif

<script>
    // AI Generate
    document.getElementById('aiGenerateBtn')?.addEventListener('click', async function () {
        const prompt = document.getElementById('aiPrompt').value.trim();
        if (!prompt) return;

        const btn = this;
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Generating...';
        document.getElementById('aiError').classList.add('d-none');

        try {
            const res = await fetch('{{ route('dashboard.templates.ai-generate') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                },
                body: JSON.stringify({
                    prompt: prompt,
                    tone: document.getElementById('aiTone').value,
                    industry: document.getElementById('aiIndustry').value,
                }),
            });

            const data = await res.json();

            if (data.error) {
                document.getElementById('aiError').textContent = data.error;
                document.getElementById('aiError').classList.remove('d-none');
            } else if (data.html) {
                @if($template->mode === 'html')
                    document.getElementById('html_body_textarea').value = data.html;
                @elseif($template->mode === 'unlayer')
                    unlayer.loadDesign({ body: { rows: [], values: {} } });
                @endif
                bootstrap.Modal.getInstance(document.getElementById('aiModal')).hide();
            }
        } catch (err) {
            document.getElementById('aiError').textContent = 'Request failed. Please try again.';
            document.getElementById('aiError').classList.remove('d-none');
        }

        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-stars me-1"></i> Generate';
    });
</script>
@endpush
@endsection
