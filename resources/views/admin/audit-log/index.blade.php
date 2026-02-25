@extends('layouts.admin')

@section('page-title', 'Audit Log')

@section('page-content')
<div class="container-fluid">
    <h1 class="h3 mb-4">Audit Log</h1>

    {{-- Filters --}}
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('admin.audit-log.index') }}" class="row g-3">
                <div class="col-md-3">
                    <label for="user" class="form-label">User</label>
                    <select name="user_id" id="user" class="form-select">
                        <option value="">All Users</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}" @selected(request('user_id') == $user->id)>
                                {{ $user->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="action" class="form-label">Action</label>
                    <select name="action" id="action" class="form-select">
                        <option value="">All Actions</option>
                        <option value="create" @selected(request('action') === 'create')>Create</option>
                        <option value="update" @selected(request('action') === 'update')>Update</option>
                        <option value="delete" @selected(request('action') === 'delete')>Delete</option>
                        <option value="login" @selected(request('action') === 'login')>Login</option>
                        <option value="logout" @selected(request('action') === 'logout')>Logout</option>
                        <option value="override" @selected(request('action') === 'override')>Override</option>
                        <option value="revoke" @selected(request('action') === 'revoke')>Revoke</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="entity_type" class="form-label">Entity Type</label>
                    <select name="entity_type" id="entity_type" class="form-select">
                        <option value="">All Types</option>
                        <option value="widget_client" @selected(request('entity_type') === 'widget_client')>Widget Client</option>
                        <option value="license_key" @selected(request('entity_type') === 'license_key')>License Key</option>
                        <option value="ticket" @selected(request('entity_type') === 'ticket')>Ticket</option>
                        <option value="webmaster" @selected(request('entity_type') === 'webmaster')>Webmaster</option>
                        <option value="credit" @selected(request('entity_type') === 'credit')>Credit</option>
                        <option value="article" @selected(request('entity_type') === 'article')>Article</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="date_from" class="form-label">Date From</label>
                    <input type="date" class="form-control" id="date_from" name="date_from" value="{{ request('date_from') }}">
                </div>
                <div class="col-md-2">
                    <label for="date_to" class="form-label">Date To</label>
                    <input type="date" class="form-control" id="date_to" name="date_to" value="{{ request('date_to') }}">
                </div>
                <div class="col-md-1 d-flex align-items-end">
                    <button type="submit" class="btn btn-outline-secondary w-100">Filter</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Audit Log Table --}}
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">Log Entries</h5>
            <span class="badge bg-secondary">{{ $logs->total() }} entries</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-sm mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th>Timestamp</th>
                            <th>User</th>
                            <th>Action</th>
                            <th>Entity Type</th>
                            <th>Entity ID</th>
                            <th>Description</th>
                            <th>IP Address</th>
                            <th>Details</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($logs as $log)
                            <tr>
                                <td class="text-nowrap">
                                    <small>{{ $log->created_at->format('M d, Y') }}</small><br>
                                    <small class="text-muted">{{ $log->created_at->format('g:i:s A') }}</small>
                                </td>
                                <td>{{ $log->user?->name ?? 'System' }}</td>
                                <td>
                                    @switch($log->action)
                                        @case('create')
                                            <span class="badge bg-success">Create</span>
                                            @break
                                        @case('update')
                                            <span class="badge bg-info text-dark">Update</span>
                                            @break
                                        @case('delete')
                                            <span class="badge bg-danger">Delete</span>
                                            @break
                                        @case('login')
                                            <span class="badge bg-primary">Login</span>
                                            @break
                                        @case('logout')
                                            <span class="badge bg-secondary">Logout</span>
                                            @break
                                        @case('override')
                                            <span class="badge bg-warning text-dark">Override</span>
                                            @break
                                        @case('revoke')
                                            <span class="badge bg-dark">Revoke</span>
                                            @break
                                        @default
                                            <span class="badge bg-light text-dark">{{ $log->action }}</span>
                                    @endswitch
                                </td>
                                <td>{{ Str::headline($log->entity_type ?? '—') }}</td>
                                <td>
                                    @if($log->entity_id)
                                        <code>{{ $log->entity_id }}</code>
                                    @else
                                        <span class="text-muted">&mdash;</span>
                                    @endif
                                </td>
                                <td>{{ Str::limit($log->description, 60) }}</td>
                                <td><small class="text-muted font-monospace">{{ $log->ip_address ?? '—' }}</small></td>
                                <td>
                                    @if($log->metadata)
                                        <button type="button" class="btn btn-sm btn-outline-secondary"
                                                data-bs-toggle="modal" data-bs-target="#detail-modal-{{ $log->id }}">
                                            <i class="bi bi-code-slash"></i>
                                        </button>

                                        {{-- Detail Modal --}}
                                        <div class="modal fade" id="detail-modal-{{ $log->id }}" tabindex="-1">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title">Audit Log Detail #{{ $log->id }}</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <pre class="bg-light p-3 rounded"><code>{{ json_encode($log->metadata, JSON_PRETTY_PRINT) }}</code></pre>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @else
                                        <span class="text-muted">&mdash;</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-4 text-muted">No audit log entries found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($logs->hasPages())
            <div class="card-footer">
                {{ $logs->withQueryString()->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
