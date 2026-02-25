@extends('layouts.client')

@section('title', 'Contact Lists')

@section('page-content')
<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h4 class="mb-1 fw-bold"><i class="bi bi-collection me-2 text-primary"></i>Contact Lists</h4>
        <p class="text-muted mb-0">Organize your contacts into segmented lists</p>
    </div>
    <a href="{{ route('dashboard.lists.create') }}" class="btn btn-primary">
        <i class="bi bi-plus-lg me-1"></i> New List
    </a>
</div>

@if($lists->isEmpty())
    <div class="card border-0 shadow-sm">
        <div class="card-body text-center py-5">
            <i class="bi bi-collection fs-1 text-muted opacity-25 d-block mb-3"></i>
            <h5 class="text-muted">No lists yet</h5>
            <p class="text-muted">Create your first contact list to start organizing your subscribers.</p>
            <a href="{{ route('dashboard.lists.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-lg me-1"></i> Create Your First List
            </a>
        </div>
    </div>
@else
    <div class="row g-3">
        @foreach($lists as $list)
            <div class="col-md-6 col-xl-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-start justify-content-between mb-3">
                            <div class="rounded-3 bg-primary bg-opacity-10 p-2">
                                <i class="bi bi-collection text-primary fs-5"></i>
                            </div>
                            <div class="dropdown">
                                <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="dropdown">
                                    <i class="bi bi-three-dots-vertical"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li>
                                        <a class="dropdown-item" href="{{ route('dashboard.lists.show', $list) }}">
                                            <i class="bi bi-eye me-2"></i>View Contacts
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="{{ route('dashboard.lists.edit', $list) }}">
                                            <i class="bi bi-pencil me-2"></i>Edit
                                        </a>
                                    </li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li>
                                        <form method="POST" action="{{ route('dashboard.lists.destroy', $list) }}"
                                              onsubmit="return confirm('Delete list \'{{ addslashes($list->name) }}\'? Contacts will not be deleted.')">
                                            @csrf
                                            @method('DELETE')
                                            <button class="dropdown-item text-danger">
                                                <i class="bi bi-trash me-2"></i>Delete
                                            </button>
                                        </form>
                                    </li>
                                </ul>
                            </div>
                        </div>

                        <h5 class="card-title mb-1">
                            <a href="{{ route('dashboard.lists.show', $list) }}"
                               class="text-decoration-none text-dark">
                                {{ $list->name }}
                            </a>
                        </h5>

                        @if($list->description)
                            <p class="card-text text-muted small mb-3">{{ $list->description }}</p>
                        @else
                            <p class="card-text text-muted small mb-3 fst-italic">No description</p>
                        @endif

                        <div class="d-flex align-items-center justify-content-between">
                            <div class="d-flex align-items-center gap-1 text-muted small">
                                <i class="bi bi-people"></i>
                                <strong class="text-dark">{{ number_format($list->contacts_count) }}</strong>
                                contact{{ $list->contacts_count !== 1 ? 's' : '' }}
                            </div>
                            <small class="text-muted">{{ $list->created_at->format('M d, Y') }}</small>
                        </div>
                    </div>
                    <div class="card-footer bg-white border-top d-flex gap-2">
                        <a href="{{ route('dashboard.lists.show', $list) }}"
                           class="btn btn-sm btn-outline-primary flex-fill">
                            <i class="bi bi-eye me-1"></i> View
                        </a>
                        <a href="{{ route('dashboard.lists.edit', $list) }}"
                           class="btn btn-sm btn-outline-secondary flex-fill">
                            <i class="bi bi-pencil me-1"></i> Edit
                        </a>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    @if($lists->hasPages())
        <div class="d-flex justify-content-center mt-4">
            {{ $lists->links() }}
        </div>
    @endif
@endif
@endsection
