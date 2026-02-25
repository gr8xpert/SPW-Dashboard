@extends('layouts.client')

@section('title', 'Credit Hours')

@section('page-content')
<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h4 class="mb-1 fw-bold"><i class="bi bi-clock-history me-2 text-primary"></i>Credit Hours</h4>
        <p class="text-muted mb-0">Your credit balance and transaction history</p>
    </div>
    <a href="{{ route('dashboard.credits.buy') }}" class="btn btn-primary">
        <i class="bi bi-plus-circle me-1"></i> Buy Credits
    </a>
</div>

{{-- Balance Card --}}
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="rounded-3 bg-primary bg-opacity-10 p-3">
                    <i class="bi bi-wallet2 fs-3 text-primary"></i>
                </div>
                <div>
                    <div class="text-muted small">Available Balance</div>
                    <div class="fw-bold fs-3">{{ number_format($balance ?? 0, 1) }} <small class="text-muted fs-6">hours</small></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="rounded-3 bg-success bg-opacity-10 p-3">
                    <i class="bi bi-arrow-down-circle fs-3 text-success"></i>
                </div>
                <div>
                    <div class="text-muted small">Total Purchased</div>
                    <div class="fw-bold fs-3">{{ number_format($totalPurchased ?? 0, 1) }} <small class="text-muted fs-6">hours</small></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="rounded-3 bg-warning bg-opacity-10 p-3">
                    <i class="bi bi-arrow-up-circle fs-3 text-warning"></i>
                </div>
                <div>
                    <div class="text-muted small">Total Used</div>
                    <div class="fw-bold fs-3">{{ number_format($totalUsed ?? 0, 1) }} <small class="text-muted fs-6">hours</small></div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Transaction History --}}
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white border-bottom pt-4 pb-3 d-flex align-items-center justify-content-between">
        <h6 class="fw-bold mb-0"><i class="bi bi-list-ul me-2 text-primary"></i>Transaction History</h6>
        <form method="GET" action="{{ route('dashboard.credits.index') }}" class="d-flex gap-2">
            <select name="type" class="form-select form-select-sm" style="width: auto;" onchange="this.form.submit()">
                <option value="">All Types</option>
                <option value="purchase" {{ request('type') === 'purchase' ? 'selected' : '' }}>Purchases</option>
                <option value="debit"    {{ request('type') === 'debit'    ? 'selected' : '' }}>Debits</option>
                <option value="refund"   {{ request('type') === 'refund'   ? 'selected' : '' }}>Refunds</option>
            </select>
        </form>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Date</th>
                    <th>Description</th>
                    <th>Type</th>
                    <th>Ticket</th>
                    <th class="text-end">Hours</th>
                    <th class="text-end">Balance After</th>
                </tr>
            </thead>
            <tbody>
                @forelse($transactions ?? [] as $txn)
                    <tr>
                        <td class="text-muted small">{{ $txn->created_at->format('M d, Y H:i') }}</td>
                        <td class="fw-medium small">{{ $txn->description }}</td>
                        <td>
                            @php
                                $typeColors = [
                                    'purchase' => 'success',
                                    'debit'    => 'warning',
                                    'refund'   => 'info',
                                ];
                                $tColor = $typeColors[$txn->type ?? 'debit'] ?? 'secondary';
                            @endphp
                            <span class="badge bg-{{ $tColor }} bg-opacity-10 text-{{ $tColor }} border border-{{ $tColor }} border-opacity-25">
                                {{ ucfirst($txn->type) }}
                            </span>
                        </td>
                        <td>
                            @if($txn->ticket_id)
                                <a href="{{ route('dashboard.tickets.show', $txn->ticket_id) }}" class="text-decoration-none small">
                                    #{{ $txn->ticket_id }}
                                </a>
                            @else
                                <span class="text-muted">--</span>
                            @endif
                        </td>
                        <td class="text-end fw-bold {{ $txn->type === 'debit' ? 'text-danger' : 'text-success' }}">
                            {{ $txn->type === 'debit' ? '-' : '+' }}{{ number_format(abs($txn->hours), 1) }}
                        </td>
                        <td class="text-end text-muted small">{{ number_format($txn->balance_after, 1) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center py-5 text-muted">
                            <i class="bi bi-clock-history fs-1 d-block mb-2 opacity-25"></i>
                            <p class="small mb-2">No transactions yet.</p>
                            <a href="{{ route('dashboard.credits.buy') }}" class="btn btn-sm btn-primary">
                                <i class="bi bi-plus-circle me-1"></i> Buy Your First Credit Pack
                            </a>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if(isset($transactions) && $transactions->hasPages())
        <div class="card-footer bg-white border-top-0 d-flex justify-content-between align-items-center">
            <small class="text-muted">
                Showing {{ $transactions->firstItem() }}--{{ $transactions->lastItem() }}
                of {{ $transactions->total() }} transactions
            </small>
            {{ $transactions->links() }}
        </div>
    @endif
</div>
@endsection
