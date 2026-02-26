@extends('layouts.admin')

@section('page-title', 'Webmasters')

@section('page-content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Webmasters</h1>
        <a href="{{ route('admin.webmasters.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-lg"></i> Add Webmaster
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- Search --}}
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('admin.webmasters.index') }}" class="row g-3">
                <div class="col-md-6">
                    <input type="text" name="search" class="form-control" placeholder="Search by name or email..." value="{{ request('search') }}">
                </div>
                <div class="col-md-3">
                    <select name="status" class="form-select">
                        <option value="">All Statuses</option>
                        <option value="active" @selected(request('status') === 'active')>Active</option>
                        <option value="inactive" @selected(request('status') === 'inactive')>Inactive</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-outline-secondary w-100">Search</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Webmasters Table --}}
    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-striped mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Company</th>
                            <th>Clients</th>
                            <th>Open Tickets</th>
                            <th>Total Tickets</th>
                            <th>Status</th>
                            <th>Joined</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($webmasters as $webmaster)
                            <tr>
                                <td>{{ $webmaster->id }}</td>
                                <td>
                                    <strong>{{ $webmaster->name }}</strong>
                                </td>
                                <td>
                                    <a href="mailto:{{ $webmaster->email }}">{{ $webmaster->email }}</a>
                                </td>
                                <td>{{ $webmaster->company ?? '—' }}</td>
                                <td>
                                    <span class="badge bg-info text-dark">{{ $webmaster->clients_count ?? 0 }}</span>
                                </td>
                                <td>
                                    @if(($webmaster->open_tickets_count ?? 0) > 0)
                                        <span class="badge bg-warning text-dark">{{ $webmaster->open_tickets_count }}</span>
                                    @else
                                        <span class="text-muted">0</span>
                                    @endif
                                </td>
                                <td>{{ $webmaster->tickets_count ?? 0 }}</td>
                                <td>
                                    @if($webmaster->is_active)
                                        <span class="badge bg-success">Active</span>
                                    @else
                                        <span class="badge bg-secondary">Inactive</span>
                                    @endif
                                </td>
                                <td>{{ $webmaster->created_at->format('M d, Y') }}</td>
                                <td>
                                    <form method="POST" action="{{ route('admin.webmasters.destroy', $webmaster) }}"
                                          onsubmit="return confirm('Remove this webmaster?')" class="d-inline">
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
                                <td colspan="10" class="text-center py-4 text-muted">No webmasters found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($webmasters->hasPages())
            <div class="card-footer">
                {{ $webmasters->withQueryString()->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
