@extends('layouts.client')

@section('title', 'Edit Automation — SmartMailer')

@section('page-content')

{{-- Page Header --}}
<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h4 class="fw-bold mb-1">
            <i class="bi bi-robot me-2 text-primary"></i>Edit: {{ $automation->name }}
        </h4>
        <p class="text-muted mb-0">Modify automation settings and workflow steps</p>
    </div>
    <div class="d-flex gap-2">
        @if($automation->status !== 'active')
            <form method="POST" action="{{ route('dashboard.automations.activate', $automation) }}">
                @csrf
                <button type="submit" class="btn btn-success btn-sm">
                    <i class="bi bi-play-fill me-1"></i> Activate
                </button>
            </form>
        @else
            <form method="POST" action="{{ route('dashboard.automations.pause', $automation) }}">
                @csrf
                <button type="submit" class="btn btn-warning btn-sm">
                    <i class="bi bi-pause-fill me-1"></i> Pause
                </button>
            </form>
        @endif

        <form method="POST" action="{{ route('dashboard.automations.destroy', $automation) }}"
              onsubmit="return confirm('Delete this automation? This cannot be undone.')">
            @csrf @method('DELETE')
            <button type="submit" class="btn btn-outline-danger btn-sm">
                <i class="bi bi-trash me-1"></i> Delete
            </button>
        </form>

        <a href="{{ route('dashboard.automations.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i> Back
        </a>
    </div>
</div>

{{-- Alerts --}}
@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif
@if($errors->any())
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

<div class="row g-4">

    {{-- Left: Settings --}}
    <div class="col-lg-5">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-transparent border-bottom py-3">
                <h6 class="fw-semibold mb-0"><i class="bi bi-sliders me-2"></i>Settings</h6>
            </div>
            <div class="card-body p-4">
                <form method="POST" action="{{ route('dashboard.automations.update', $automation) }}">
                    @csrf @method('PUT')

                    <div class="mb-3">
                        <label class="form-label fw-medium" for="name">Automation Name <span class="text-danger">*</span></label>
                        <input type="text" id="name" name="name"
                               class="form-control @error('name') is-invalid @enderror"
                               value="{{ old('name', $automation->name) }}" required>
                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-medium" for="trigger_type">Trigger <span class="text-danger">*</span></label>
                        <select id="trigger_type" name="trigger_type"
                                class="form-select @error('trigger_type') is-invalid @enderror" required>
                            @php
                                $triggers = [
                                    'contact_added'    => 'When a contact is added',
                                    'list_subscribed'  => 'When contact is added to a list',
                                    'tag_added'        => 'When a tag is added',
                                    'contact_updated'  => 'When contact is updated',
                                    'date_field'       => 'On a specific date field',
                                    'manual'           => 'Manual trigger',
                                    'engagement_drop'  => 'When engagement drops',
                                ];
                            @endphp
                            @foreach($triggers as $value => $label)
                                <option value="{{ $value }}" {{ old('trigger_type', $automation->trigger_type) === $value ? 'selected' : '' }}>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                        @error('trigger_type')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-medium">Status</label>
                        <div>
                            @php
                                $badge = match($automation->status) {
                                    'active'  => 'success',
                                    'paused'  => 'warning',
                                    default   => 'secondary',
                                };
                            @endphp
                            <span class="badge bg-{{ $badge }}">{{ ucfirst($automation->status) }}</span>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save me-1"></i> Save Settings
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Right: Workflow Steps --}}
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-transparent border-bottom py-3 d-flex align-items-center justify-content-between">
                <h6 class="fw-semibold mb-0"><i class="bi bi-diagram-3 me-2"></i>Workflow Steps</h6>
                <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addStepModal">
                    <i class="bi bi-plus-lg me-1"></i> Add Step
                </button>
            </div>
            <div class="card-body p-4">

                @php $steps = $automation->steps; @endphp

                @if($steps->isNotEmpty())
                    <ol class="list-group list-group-flush list-group-numbered mb-0">
                        @foreach($steps as $step)
                            @php $cfg = $step->config ?? []; @endphp
                            <li class="list-group-item d-flex align-items-start justify-content-between gap-3 px-0">
                                <div class="d-flex align-items-start gap-3">
                                    <div class="rounded-2 bg-primary bg-opacity-10 p-2 flex-shrink-0 mt-1">
                                        <i class="bi bi-envelope-fill text-primary"></i>
                                    </div>
                                    <div>
                                        <div class="fw-medium">Send Email</div>
                                        <div class="small text-muted">
                                            Subject: <em>{{ $cfg['subject'] ?? '—' }}</em>
                                        </div>
                                        @if(($cfg['delay_minutes'] ?? 0) > 0)
                                            <div class="small text-muted">
                                                <i class="bi bi-clock me-1"></i>Delay: {{ $cfg['delay_minutes'] }} minute(s)
                                            </div>
                                        @endif
                                    </div>
                                </div>
                                <form method="POST"
                                      action="{{ route('dashboard.automations.steps.destroy', [$automation, $step]) }}"
                                      onsubmit="return confirm('Remove this step?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger mt-1">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </li>
                        @endforeach
                    </ol>
                @else
                    <div class="text-center text-muted py-5">
                        <i class="bi bi-diagram-3 fs-1 d-block mb-3 opacity-25"></i>
                        <p class="fw-semibold mb-1">No steps yet</p>
                        <p class="small mb-3">Click <strong>Add Step</strong> to build your workflow.</p>
                        <button type="button" class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addStepModal">
                            <i class="bi bi-plus-lg me-1"></i> Add First Step
                        </button>
                    </div>
                @endif

            </div>
        </div>
    </div>

</div>

{{-- Add Step Modal --}}
<div class="modal fade" id="addStepModal" tabindex="-1" aria-labelledby="addStepModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" action="{{ route('dashboard.automations.steps.store', $automation) }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title fw-semibold" id="addStepModalLabel">
                        <i class="bi bi-plus-circle me-2 text-primary"></i>Add Workflow Step
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">

                    {{-- Step type (only send_email for now) --}}
                    <input type="hidden" name="step_type" value="send_email">

                    <div class="mb-3">
                        <label class="form-label fw-medium">Step Type</label>
                        <div class="form-control bg-light">
                            <i class="bi bi-envelope me-2 text-primary"></i>Send Email
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-medium" for="stepDelay">
                            Delay Before Sending
                        </label>
                        <div class="input-group" style="max-width: 200px;">
                            <input type="number" id="stepDelay" name="config[delay_minutes]"
                                   class="form-control" value="0" min="0" placeholder="0">
                            <span class="input-group-text">minutes</span>
                        </div>
                        <div class="form-text">Set to 0 to send immediately when triggered.</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-medium" for="stepSubject">
                            Email Subject <span class="text-danger">*</span>
                        </label>
                        <input type="text" id="stepSubject" name="config[subject]"
                               class="form-control" required
                               placeholder="Hello {{first_name}}, welcome aboard!">
                        <div class="form-text">You can use <code>{{first_name}}</code>, <code>{{last_name}}</code>, <code>{{email}}</code>, <code>{{company}}</code>.</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-medium" for="stepHtml">
                            Email Body (HTML) <span class="text-danger">*</span>
                        </label>
                        <textarea id="stepHtml" name="config[html]" rows="8"
                                  class="form-control font-monospace" required
                                  placeholder="<p>Hi {{first_name}},</p>&#10;<p>Thanks for joining us!</p>"></textarea>
                        <div class="form-text">Write plain text or HTML. Merge tags: <code>{{first_name}}</code>, <code>{{last_name}}</code>, <code>{{email}}</code>, <code>{{company}}</code>.</div>
                    </div>

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-plus-lg me-1"></i> Add Step
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection
