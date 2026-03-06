@extends('layouts.client')

@section('title', 'Widget Labels')

@section('page-content')
<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h4 class="mb-1 fw-bold"><i class="bi bi-translate me-2 text-primary"></i>Widget Labels</h4>
        <p class="text-muted mb-0">Customize the text displayed on your property widget</p>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
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
                        <a href="{{ route('dashboard.labels.index', ['language' => $language]) }}" class="btn btn-outline-secondary">
                            <i class="bi bi-x"></i>
                        </a>
                    @endif
                </form>
            </div>
            <div class="col-md-4 text-end">
                <span class="badge bg-primary">{{ $total }} labels</span>
            </div>
        </div>
    </div>

    <div class="card-body p-0">
        @if(count($labels) > 0)
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th style="width: 30%">Label Key</th>
                            <th style="width: 25%">Default Value</th>
                            <th style="width: 30%">Your Value</th>
                            <th style="width: 15%" class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($labels as $key => $data)
                            <tr class="{{ $data['is_overridden'] ? 'table-info' : '' }}">
                                <td class="font-monospace small text-muted">{{ $key }}</td>
                                <td class="text-muted">{{ Str::limit($data['default_value'], 40) }}</td>
                                <td>
                                    <form method="POST" action="{{ route('dashboard.labels.update') }}" class="d-flex gap-2">
                                        @csrf
                                        <input type="hidden" name="language" value="{{ $language }}">
                                        <input type="hidden" name="label_key" value="{{ $key }}">
                                        <input type="text" class="form-control form-control-sm" name="label_value"
                                               value="{{ $data['current_value'] }}"
                                               placeholder="{{ $data['default_value'] }}">
                                        <button type="submit" class="btn btn-sm btn-outline-primary" title="Save">
                                            <i class="bi bi-check"></i>
                                        </button>
                                    </form>
                                </td>
                                <td class="text-end">
                                    @if($data['is_overridden'])
                                        <form method="POST" action="{{ route('dashboard.labels.reset') }}" class="d-inline">
                                            @csrf
                                            <input type="hidden" name="language" value="{{ $language }}">
                                            <input type="hidden" name="label_key" value="{{ $key }}">
                                            <button type="submit" class="btn btn-sm btn-outline-secondary" title="Reset to default">
                                                <i class="bi bi-arrow-counterclockwise"></i>
                                            </button>
                                        </form>
                                    @else
                                        <span class="text-muted small">Default</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Simple pagination --}}
            @if($total > $perPage)
                <div class="card-footer bg-white border-top">
                    <nav>
                        <ul class="pagination pagination-sm mb-0 justify-content-center">
                            @for($i = 1; $i <= ceil($total / $perPage); $i++)
                                <li class="page-item {{ $page == $i ? 'active' : '' }}">
                                    <a class="page-link" href="{{ route('dashboard.labels.index', ['language' => $language, 'search' => $search, 'page' => $i]) }}">
                                        {{ $i }}
                                    </a>
                                </li>
                            @endfor
                        </ul>
                    </nav>
                </div>
            @endif
        @else
            <div class="text-center py-5 text-muted">
                <i class="bi bi-translate display-6 d-block mb-2"></i>
                @if($search)
                    No labels found matching "{{ $search }}".
                @else
                    No default labels have been configured yet.
                    <br>
                    <span class="small">Contact support to set up widget labels.</span>
                @endif
            </div>
        @endif
    </div>
</div>

<div class="mt-3 text-muted small">
    <i class="bi bi-info-circle"></i>
    Labels highlighted in <span class="badge bg-info text-dark">blue</span> have been customized. Click "Reset" to revert to the default value.
</div>

<script>
function changeLanguage(lang) {
    window.location.href = '{{ route("dashboard.labels.index") }}?language=' + lang;
}
</script>
@endsection
