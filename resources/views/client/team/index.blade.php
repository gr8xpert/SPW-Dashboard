@extends('layouts.client')

@section('title', 'Team Members')

@section('page-content')
<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h4 class="mb-1 fw-bold"><i class="bi bi-person-badge me-2 text-primary"></i>Team Members</h4>
        <p class="text-muted mb-0">Manage who has access to your SmartMailer account</p>
    </div>
    <a href="{{ route('dashboard.team.create') }}" class="btn btn-primary">
        <i class="bi bi-person-plus me-1"></i> Invite Member
    </a>
</div>

{{-- Role Legend --}}
<div class="alert alert-light border mb-4">
    <div class="d-flex flex-wrap gap-4">
        <div>
            <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25 me-2">Admin</span>
            <small class="text-muted">Full access — can manage all settings, team, and billing</small>
        </div>
        <div>
            <span class="badge bg-warning bg-opacity-10 text-warning border border-warning border-opacity-25 me-2">Editor</span>
            <small class="text-muted">Can create and edit campaigns, templates, and contacts</small>
        </div>
        <div>
            <span class="badge bg-info bg-opacity-10 text-info border border-info border-opacity-25 me-2">Viewer</span>
            <small class="text-muted">Read-only access — can view reports and campaigns</small>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Status</th>
                    <th>Joined</th>
                    <th width="140">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($members as $member)
                    @php
                        $isCurrentUser = auth()->id() === $member->id;
                        $roleColors = [
                            'admin'  => 'danger',
                            'editor' => 'warning',
                            'viewer' => 'info',
                        ];
                        $roleColor = $roleColors[$member->role] ?? 'secondary';

                        $statusColors = [
                            'active'    => 'success',
                            'invited'   => 'info',
                            'suspended' => 'danger',
                            'inactive'  => 'secondary',
                        ];
                        $statusColor = $statusColors[$member->status] ?? 'secondary';
                    @endphp
                    <tr>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <div class="rounded-circle d-flex align-items-center justify-content-center bg-primary bg-opacity-10 text-primary fw-bold"
                                     style="width:36px; height:36px; font-size:15px; flex-shrink:0;">
                                    {{ strtoupper(substr($member->name, 0, 1)) }}
                                </div>
                                <div>
                                    <div class="fw-medium">
                                        {{ $member->name }}
                                        @if($isCurrentUser)
                                            <span class="badge bg-secondary bg-opacity-10 text-secondary border ms-1" style="font-size:10px;">You</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td class="text-muted small">{{ $member->email }}</td>
                        <td>
                            <span class="badge bg-{{ $roleColor }} bg-opacity-10 text-{{ $roleColor }} border border-{{ $roleColor }} border-opacity-25 text-capitalize">
                                {{ ucfirst($member->role) }}
                            </span>
                        </td>
                        <td>
                            <span class="badge bg-{{ $statusColor }} bg-opacity-10 text-{{ $statusColor }} border border-{{ $statusColor }} border-opacity-25">
                                {{ ucfirst($member->status) }}
                            </span>
                        </td>
                        <td class="text-muted small">{{ $member->created_at->format('M d, Y') }}</td>
                        <td>
                            <div class="d-flex gap-1">
                                {{-- Edit --}}
                                <a href="{{ route('dashboard.team.edit', $member) }}"
                                   class="btn btn-sm btn-outline-primary" title="Edit member">
                                    <i class="bi bi-pencil"></i>
                                </a>

                                {{-- Remove --}}
                                <form method="POST"
                                      action="{{ route('dashboard.team.destroy', $member) }}"
                                      onsubmit="return confirm('Remove {{ addslashes($member->name) }} from your team? They will lose access immediately.')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit"
                                            class="btn btn-sm btn-outline-danger"
                                            title="Remove member"
                                            {{ $isCurrentUser ? 'disabled' : '' }}>
                                        <i class="bi bi-person-x"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center py-5 text-muted">
                            <i class="bi bi-people fs-1 d-block mb-2 opacity-25"></i>
                            No team members yet.
                            <a href="{{ route('dashboard.team.create') }}">Invite your first member</a>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
