@extends('layouts.client')

@section('title', 'SMTP Accounts')

@section('page-content')
<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h4 class="mb-1 fw-bold"><i class="bi bi-server me-2 text-primary"></i>SMTP Accounts</h4>
        <p class="text-muted mb-0">Manage your email sending accounts and providers</p>
    </div>
    <a href="{{ route('dashboard.smtp-accounts.create') }}" class="btn btn-primary">
        <i class="bi bi-plus-lg me-1"></i> Add SMTP Account
    </a>
</div>

<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Name</th>
                    <th>Provider</th>
                    <th>Host : Port</th>
                    <th>From Email</th>
                    <th>Daily Limit</th>
                    <th>Today's Sent</th>
                    <th>Reputation</th>
                    <th width="50" class="text-center">Default</th>
                    <th width="180">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($accounts as $account)
                    <tr>
                        <td class="fw-medium">{{ $account->name }}</td>
                        <td>
                            @php
                                $providerColors = [
                                    'ses'       => 'warning',
                                    'sendgrid'  => 'primary',
                                    'mailgun'   => 'danger',
                                    'postmark'  => 'dark',
                                    'smtp'      => 'secondary',
                                    'platform'  => 'info',
                                ];
                                $color = $providerColors[$account->provider] ?? 'secondary';
                            @endphp
                            <span class="badge bg-{{ $color }} bg-opacity-10 text-{{ $color }} border border-{{ $color }} border-opacity-25 text-uppercase">
                                {{ $account->provider }}
                            </span>
                        </td>
                        <td class="text-muted small">
                            @if($account->host)
                                {{ $account->host }}<span class="text-muted opacity-50">:</span>{{ $account->port }}
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td class="small">{{ $account->from_email ?: '—' }}</td>
                        <td class="small">
                            @if($account->daily_limit)
                                {{ number_format($account->daily_limit) }}
                            @else
                                <span class="text-muted">Unlimited</span>
                            @endif
                        </td>
                        <td class="small">
                            <span class="{{ $account->emails_sent_today > 0 ? 'text-dark fw-medium' : 'text-muted' }}">
                                {{ number_format($account->emails_sent_today) }}
                            </span>
                            @if($account->daily_limit && $account->daily_limit > 0)
                                <div class="progress mt-1" style="height:4px; min-width:60px;">
                                    @php $pct = min(100, round($account->emails_sent_today / $account->daily_limit * 100)) @endphp
                                    <div class="progress-bar bg-{{ $pct >= 90 ? 'danger' : ($pct >= 70 ? 'warning' : 'success') }}"
                                         style="width:{{ $pct }}%"></div>
                                </div>
                            @endif
                        </td>
                        <td>
                            @php
                                $score = $account->reputation_score ?? 0;
                                $repColor = $score >= 80 ? 'success' : ($score >= 50 ? 'warning' : 'danger');
                            @endphp
                            <div class="d-flex align-items-center gap-2">
                                <div class="progress flex-grow-1" style="height:6px; min-width:60px;">
                                    <div class="progress-bar bg-{{ $repColor }}" style="width:{{ $score }}%"></div>
                                </div>
                                <span class="small text-{{ $repColor }} fw-medium">{{ $score }}%</span>
                            </div>
                        </td>
                        <td class="text-center">
                            @if($account->is_default)
                                <i class="bi bi-star-fill text-warning fs-5" title="Default account"></i>
                            @else
                                <i class="bi bi-star text-muted fs-5"></i>
                            @endif
                        </td>
                        <td>
                            <div class="d-flex gap-1 flex-wrap">
                                {{-- Test --}}
                                <form method="POST" action="{{ route('dashboard.smtp-accounts.test', $account) }}"
                                      onsubmit="return confirm('Send a test email through this account?')">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-outline-info" title="Send Test Email">
                                        <i class="bi bi-send-check"></i>
                                    </button>
                                </form>

                                {{-- Set Default --}}
                                @unless($account->is_default)
                                    <form method="POST" action="{{ route('dashboard.smtp-accounts.set-default', $account) }}"
                                          onsubmit="return confirm('Set this as the default sending account?')">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-outline-warning" title="Set as Default">
                                            <i class="bi bi-star"></i>
                                        </button>
                                    </form>
                                @endunless

                                {{-- Edit --}}
                                <a href="{{ route('dashboard.smtp-accounts.edit', $account) }}"
                                   class="btn btn-sm btn-outline-primary" title="Edit">
                                    <i class="bi bi-pencil"></i>
                                </a>

                                {{-- Delete --}}
                                <form method="POST" action="{{ route('dashboard.smtp-accounts.destroy', $account) }}"
                                      onsubmit="return confirm('Are you sure you want to delete this SMTP account? This action cannot be undone.')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="text-center py-5 text-muted">
                            <i class="bi bi-server fs-1 d-block mb-2 opacity-25"></i>
                            No SMTP accounts configured yet.
                            <a href="{{ route('dashboard.smtp-accounts.create') }}">Add your first account</a>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($accounts->hasPages())
        <div class="card-footer bg-white border-top-0 d-flex justify-content-between align-items-center">
            <small class="text-muted">
                Showing {{ $accounts->firstItem() }}–{{ $accounts->lastItem() }}
                of {{ $accounts->total() }} accounts
            </small>
            {{ $accounts->links() }}
        </div>
    @endif
</div>
@endsection
