@extends('layouts.admin')

@section('title', 'Global Suppressions — Smart Property Management Admin')
@section('page-title', 'Global Suppressions')

@section('page-content')


<div class="d-flex align-items-start justify-content-between mb-4">
    <div>
        <h4 class="fw-bold mb-1">
            <i class="bi bi-slash-circle me-2 text-danger"></i>Global Suppressions
        </h4>
        <p class="text-muted mb-0">
            Emails on this list will never receive mail from any client on this platform,
            regardless of their subscription status.
        </p>
    </div>
    <span class="badge bg-danger fs-6 mt-1">
        {{ $suppressions->total() }} {{ Str::plural('entry', $suppressions->total()) }}
    </span>
</div>

<div class="row g-4">
    {{-- Add Form --}}
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-transparent border-0 pt-3">
                <h6 class="fw-semibold mb-0">
                    <i class="bi bi-plus-circle me-2 text-danger"></i>Add Suppression
                </h6>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('admin.suppressions.store') }}">
                    @csrf
                    <div class="mb-3">
                        <label for="suppressEmail" class="form-label fw-medium">
                            Email Address <span class="text-danger">*</span>
                        </label>
                        <input type="email"
                               id="suppressEmail"
                               name="email"
                               class="form-control @error('email') is-invalid @enderror"
                               placeholder="someone@example.com"
                               value="{{ old('email') }}"
                               required>
                        @error('email')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mb-3">
                        <label for="suppressReason" class="form-label fw-medium">Reason</label>
                        <select id="suppressReason" name="reason" class="form-select">
                            <option value="manual" {{ old('reason') == 'manual' ? 'selected' : '' }}>Manual</option>
                            <option value="hard_bounce" {{ old('reason') == 'hard_bounce' ? 'selected' : '' }}>Hard Bounce</option>
                            <option value="complaint" {{ old('reason') == 'complaint' ? 'selected' : '' }}>Complaint</option>
                            <option value="spam_trap" {{ old('reason') == 'spam_trap' ? 'selected' : '' }}>Spam Trap</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-danger w-100">
                        <i class="bi bi-slash-circle me-1"></i> Add to Suppressions
                    </button>
                </form>
            </div>
        </div>

        <div class="alert alert-warning border-0 mt-3 shadow-sm">
            <i class="bi bi-exclamation-triangle me-2"></i>
            <strong>Use with caution.</strong> Suppressed emails will be silently skipped
            during all sends across all clients.
        </div>
    </div>

    {{-- Suppressions Table --}}
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-transparent border-0 pt-3 d-flex align-items-center justify-content-between">
                <h6 class="fw-semibold mb-0">Suppressed Addresses</h6>
                <span class="text-muted small">Showing newest first</span>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Email Address</th>
                            <th>Added At</th>
                            <th width="80" class="text-end">Remove</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($suppressions as $suppression)
                            <tr>
                                <td>
                                    <i class="bi bi-slash-circle text-danger me-2 small"></i>
                                    <span class="font-monospace">{{ $suppression->email }}</span>
                                </td>
                                <td class="text-muted small">
                                    {{ \Carbon\Carbon::parse($suppression->added_at)->format('M d, Y H:i') }}
                                    <span class="text-muted ms-1">
                                        ({{ \Carbon\Carbon::parse($suppression->added_at)->diffForHumans() }})
                                    </span>
                                </td>
                                <td class="text-end">
                                    <form method="POST"
                                          action="{{ route('admin.suppressions.destroy', $suppression->id) }}"
                                          onsubmit="return confirm('Remove {{ addslashes($suppression->email) }} from suppressions?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Remove suppression">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="text-center py-5 text-muted">
                                    <i class="bi bi-check-circle fs-1 d-block mb-2 opacity-25 text-success"></i>
                                    No suppressed addresses. All clear!
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($suppressions->hasPages())
                <div class="card-footer bg-white border-top d-flex justify-content-between align-items-center">
                    <small class="text-muted">
                        Showing {{ $suppressions->firstItem() }}–{{ $suppressions->lastItem() }}
                        of {{ $suppressions->total() }} suppressions
                    </small>
                    {{ $suppressions->links() }}
                </div>
            @endif
        </div>
    </div>
</div>

@endsection
