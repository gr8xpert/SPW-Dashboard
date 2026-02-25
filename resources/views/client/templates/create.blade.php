@extends('layouts.client')

@section('title', 'Create Template')

@section('page-content')
<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h4 class="mb-1 fw-bold"><i class="bi bi-file-earmark-plus me-2 text-primary"></i>Create Template</h4>
        <p class="text-muted mb-0">Choose your editor and build a reusable email template</p>
    </div>
    <a href="{{ route('dashboard.templates.index') }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i> Back to Templates
    </a>
</div>

<form method="POST" action="{{ route('dashboard.templates.store') }}" id="templateForm">
    @csrf
    <input type="hidden" name="html_content" id="html_body_input">
    <input type="hidden" name="json_design"  id="json_body_input">
    <input type="hidden" name="mode" id="editor_type_input" value="{{ old('mode', '') }}">

    {{-- Step 1: Choose editor type (shown when no editor selected) --}}
    <div id="editorChooser" class="{{ old('mode') ? 'd-none' : '' }}">
        <div class="row justify-content-center">
            <div class="col-lg-9">
                <h5 class="text-center mb-4 text-muted fw-normal">How would you like to build this template?</h5>
                <div class="row g-3">
                    {{-- Unlayer --}}
                    <div class="col-md-4">
                        <div class="card border-2 border-primary shadow-sm h-100 text-center p-4 cursor-pointer editor-card"
                             data-type="unlayer" role="button">
                            <div class="mb-3">
                                <span class="display-4"><i class="bi bi-magic text-primary"></i></span>
                            </div>
                            <h5 class="fw-semibold mb-2">Visual Builder</h5>
                            <p class="text-muted small mb-0">
                                Drag-and-drop editor. No coding required. Perfect for beautiful, responsive emails.
                            </p>
                            <span class="badge bg-primary mt-3">Recommended</span>
                        </div>
                    </div>
                    {{-- HTML --}}
                    <div class="col-md-4">
                        <div class="card border shadow-sm h-100 text-center p-4 cursor-pointer editor-card"
                             data-type="html" role="button">
                            <div class="mb-3">
                                <span class="display-4"><i class="bi bi-code-slash text-warning"></i></span>
                            </div>
                            <h5 class="fw-semibold mb-2">HTML Code</h5>
                            <p class="text-muted small mb-0">
                                Write raw HTML. Full control for developers who know email HTML.
                            </p>
                        </div>
                    </div>
                    {{-- Plain Text --}}
                    <div class="col-md-4">
                        <div class="card border shadow-sm h-100 text-center p-4 cursor-pointer editor-card"
                             data-type="plain" role="button">
                            <div class="mb-3">
                                <span class="display-4"><i class="bi bi-file-text text-secondary"></i></span>
                            </div>
                            <h5 class="fw-semibold mb-2">Plain Text</h5>
                            <p class="text-muted small mb-0">
                                Simple text-only emails. Great for personal, transactional messages.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Step 2: Template details + editor (shown after choosing editor type) --}}
    <div id="editorSection" class="{{ old('mode') ? '' : 'd-none' }}">
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
                                   value="{{ old('name') }}" placeholder="My Email Template" required>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-medium small">Subject Line</label>
                            <input type="text" name="subject"
                                   class="form-control @error('subject') is-invalid @enderror"
                                   value="{{ old('subject') }}" placeholder="Your email subject...">
                            @error('subject')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text">Default subject for campaigns using this template.</div>
                        </div>

                        @if($folders->count())
                        <div class="mb-3">
                            <label class="form-label fw-medium small">Folder</label>
                            <select name="folder_id" class="form-select">
                                <option value="">No Folder</option>
                                @foreach($folders as $folder)
                                    <option value="{{ $folder->id }}" {{ old('folder_id') == $folder->id ? 'selected' : '' }}>
                                        {{ $folder->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        @endif

                        <div class="mb-3">
                            <label class="form-label fw-medium small">Editor Type</label>
                            <div id="editorTypeDisplay" class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 w-100 py-2">
                                Visual Builder
                            </div>
                        </div>

                        <hr>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary" id="saveBtn">
                                <i class="bi bi-save me-1"></i> Save Template
                            </button>
                            <button type="button" class="btn btn-outline-secondary btn-sm"
                                    onclick="changeEditor()">
                                <i class="bi bi-arrow-left me-1"></i> Change Editor
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Editor Area --}}
            <div class="col-lg-9">
                {{-- Unlayer Editor --}}
                <div id="unlayerEditor" class="d-none">
                    <div class="card border-0 shadow-sm mb-3">
                        <div class="card-header bg-white d-flex align-items-center justify-content-between py-2">
                            <span class="fw-medium"><i class="bi bi-magic me-2 text-primary"></i>Visual Builder</span>
                            <div class="d-flex gap-2">
                                <button type="button" class="btn btn-sm btn-outline-secondary"
                                        data-bs-toggle="modal" data-bs-target="#aiModal">
                                    <i class="bi bi-stars me-1 text-warning"></i> AI Generate
                                </button>
                            </div>
                        </div>
                    </div>
                    <div id="unlayer-container"
                         style="height:600px; border:1px solid #dee2e6; border-radius:8px; overflow:hidden;">
                    </div>
                </div>

                {{-- HTML Editor --}}
                <div id="htmlEditor" class="d-none">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-white d-flex align-items-center justify-content-between py-2">
                            <span class="fw-medium"><i class="bi bi-code-slash me-2 text-warning"></i>HTML Editor</span>
                            <button type="button" class="btn btn-sm btn-outline-secondary"
                                    data-bs-toggle="modal" data-bs-target="#aiModal">
                                <i class="bi bi-stars me-1 text-warning"></i> AI Generate
                            </button>
                        </div>
                        <div class="card-body p-0">
                            <textarea name="html_content" id="html_body_textarea" rows="28"
                                      class="form-control border-0 rounded-0 font-monospace"
                                      style="resize:none; font-size:13px;"
                                      placeholder="<!DOCTYPE html>&#10;<html>&#10;  <!-- Your HTML email code here -->&#10;</html>">{{ old('html_content') }}</textarea>
                        </div>
                    </div>
                </div>

                {{-- Plain Text Editor --}}
                <div id="plainEditor" class="d-none">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-white py-2">
                            <span class="fw-medium"><i class="bi bi-file-text me-2 text-secondary"></i>Plain Text Editor</span>
                        </div>
                        <div class="card-body p-0">
                            <textarea name="plain_text_content" id="plain_text_textarea" rows="28"
                                      class="form-control border-0 rounded-0"
                                      style="resize:none; font-size:14px;"
                                      placeholder="Write your plain text email here...&#10;&#10;Use @{{first_name}} for personalization.">{{ old('plain_text_content') }}</textarea>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
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
                <div class="mb-3">
                    <label class="form-label fw-medium">Describe your email <span class="text-danger">*</span></label>
                    <textarea id="aiPrompt" class="form-control" rows="3"
                              placeholder="e.g. A welcome email for new SaaS users with a getting started guide..."></textarea>
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
<script src="https://editor.unlayer.com/embed.js"></script>
<script>
    let currentEditorType = '{{ old('mode', '') }}';
    let unlayerInitialized = false;

    // Editor card selection
    document.querySelectorAll('.editor-card').forEach(card => {
        card.addEventListener('click', function () {
            selectEditor(this.dataset.type);
        });
    });

    function selectEditor(type) {
        currentEditorType = type;
        document.getElementById('editor_type_input').value = type;
        document.getElementById('editorChooser').classList.add('d-none');
        document.getElementById('editorSection').classList.remove('d-none');

        // Update badge
        const typeLabels = { unlayer: 'Visual Builder', html: 'HTML Code', plain: 'Plain Text' };
        document.getElementById('editorTypeDisplay').textContent = typeLabels[type] || type;

        // Show correct editor
        document.getElementById('unlayerEditor').classList.add('d-none');
        document.getElementById('htmlEditor').classList.add('d-none');
        document.getElementById('plainEditor').classList.add('d-none');

        if (type === 'unlayer') {
            document.getElementById('unlayerEditor').classList.remove('d-none');
            initUnlayer();
        } else if (type === 'html') {
            document.getElementById('htmlEditor').classList.remove('d-none');
        } else if (type === 'plain') {
            document.getElementById('plainEditor').classList.remove('d-none');
        }
    }

    function changeEditor() {
        document.getElementById('editorSection').classList.add('d-none');
        document.getElementById('editorChooser').classList.remove('d-none');
    }

    function initUnlayer() {
        if (unlayerInitialized) return;
        unlayerInitialized = true;

        unlayer.init({
            id: 'unlayer-container',
            projectId: 1,
            displayMode: 'email',
        });
    }

    // Before submit — export unlayer design
    document.getElementById('templateForm').addEventListener('submit', function (e) {
        if (currentEditorType === 'unlayer' && unlayerInitialized) {
            e.preventDefault();
            unlayer.exportHtml(function (data) {
                document.getElementById('html_body_input').value = data.html;
                document.getElementById('json_body_input').value = JSON.stringify(data.design);
                document.getElementById('templateForm').submit();
            });
        } else if (currentEditorType === 'html') {
            document.getElementById('html_body_input').value =
                document.getElementById('html_body_textarea').value;
        }
    });

    // Restore editor if old() exists
    @if(old('mode'))
        selectEditor('{{ old('mode') }}');
    @endif

    // AI Generate
    document.getElementById('aiGenerateBtn').addEventListener('click', async function () {
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
                if (currentEditorType === 'html') {
                    document.getElementById('html_body_textarea').value = data.html;
                } else if (currentEditorType === 'unlayer' && unlayerInitialized) {
                    unlayer.loadDesign({ html: data.html });
                }
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
