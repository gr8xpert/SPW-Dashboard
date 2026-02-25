@extends('layouts.client')

@section('title', 'Billing & Subscription')

@section('page-content')
<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h4 class="mb-1 fw-bold"><i class="bi bi-credit-card me-2 text-primary"></i>Billing & Subscription</h4>
        <p class="text-muted mb-0">Manage your plan, subscription, and payment details</p>
    </div>
</div>

{{-- Current Plan Card --}}
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white border-bottom pt-4 pb-3">
        <h6 class="fw-bold mb-0"><i class="bi bi-gem me-2 text-primary"></i>Current Subscription</h6>
    </div>
    <div class="card-body p-4">
        <div class="row align-items-center g-3">
            <div class="col-md-6">
                <div class="d-flex align-items-center gap-4">
                    <div class="rounded-3 bg-primary bg-opacity-10 p-3">
                        <i class="bi bi-gem fs-3 text-primary"></i>
                    </div>
                    <div>
                        <div class="fw-bold fs-4">{{ $client->plan->name ?? 'Free' }}</div>
                        <div class="d-flex align-items-center gap-2 mt-1">
                            @php
                                $statusColors = [
                                    'active'    => 'success',
                                    'trialing'  => 'info',
                                    'suspended' => 'danger',
                                    'cancelled' => 'secondary',
                                ];
                                $statusColor = $statusColors[$client->status] ?? 'secondary';
                            @endphp
                            <span class="badge bg-{{ $statusColor }} bg-opacity-10 text-{{ $statusColor }} border border-{{ $statusColor }} border-opacity-25">
                                {{ ucfirst($client->status) }}
                            </span>
                            @if($client->status === 'trialing' && $client->trial_ends_at)
                                <small class="text-muted">
                                    Trial ends {{ \Carbon\Carbon::parse($client->trial_ends_at)->format('M d, Y') }}
                                    ({{ \Carbon\Carbon::parse($client->trial_ends_at)->diffForHumans() }})
                                </small>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 text-md-center">
                @if($client->plan && $client->plan->price_monthly > 0)
                    <div class="fs-3 fw-bold text-primary">${{ number_format($client->plan->price_monthly, 2) }}</div>
                    <div class="text-muted small">per month</div>
                @else
                    <div class="fs-3 fw-bold text-success">Free</div>
                    <div class="text-muted small">no charge</div>
                @endif
            </div>
            <div class="col-md-3 text-md-end">
                @if(in_array($client->status, ['active', 'trialing']))
                    <form method="POST" action="{{ route('dashboard.billing.cancel') }}"
                          onsubmit="return confirm('Are you sure you want to cancel your subscription? Your access will continue until the end of the current billing period.')">
                        @csrf
                        <button type="submit" class="btn btn-outline-danger btn-sm">
                            <i class="bi bi-x-circle me-1"></i> Cancel Subscription
                        </button>
                    </form>
                @elseif($client->status === 'cancelled')
                    <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25 px-3 py-2">
                        <i class="bi bi-x-circle me-1"></i> Subscription Cancelled
                    </span>
                @endif
            </div>
        </div>
    </div>
</div>

{{-- Plans Grid --}}
<div class="mb-3">
    <h5 class="fw-bold mb-1">Available Plans</h5>
    <p class="text-muted mb-4">Choose the plan that best fits your needs. You can upgrade or downgrade at any time.</p>
</div>

<div class="row g-4">
    @forelse($plans as $plan)
        @php
            $isCurrentPlan = $client->plan && $client->plan->id === $plan->id;
            $features = is_array($plan->features) ? $plan->features : json_decode($plan->features ?? '[]', true) ?? [];
        @endphp
        <div class="col-md-4">
            <div class="card h-100 border-0 shadow-sm {{ $isCurrentPlan ? 'border border-2 border-primary' : '' }}"
                 style="{{ $isCurrentPlan ? 'border-color: var(--bs-primary) !important;' : '' }}">

                @if($isCurrentPlan)
                    <div class="card-header bg-primary text-white text-center py-2 rounded-top">
                        <small class="fw-bold text-uppercase letter-spacing-1">
                            <i class="bi bi-check-circle me-1"></i> Current Plan
                        </small>
                    </div>
                @endif

                <div class="card-body p-4 d-flex flex-column">
                    <div class="mb-3">
                        <h5 class="fw-bold mb-1">{{ $plan->name }}</h5>
                        <div class="d-flex align-items-baseline gap-1">
                            <span class="fs-2 fw-bold text-primary">
                                ${{ number_format($plan->price_monthly, 0) }}
                            </span>
                            <span class="text-muted">/mo</span>
                        </div>
                        @if($plan->price_yearly)
                            <small class="text-success">
                                <i class="bi bi-tag me-1"></i>
                                ${{ number_format($plan->price_yearly / 12, 0) }}/mo billed yearly
                            </small>
                        @endif
                    </div>

                    <hr>

                    {{-- Key Limits --}}
                    <ul class="list-unstyled mb-3">
                        <li class="d-flex align-items-center gap-2 py-1">
                            <i class="bi bi-people text-primary"></i>
                            <span class="small">
                                @if($plan->max_contacts)
                                    {{ number_format($plan->max_contacts) }} contacts
                                @else
                                    Unlimited contacts
                                @endif
                            </span>
                        </li>
                        <li class="d-flex align-items-center gap-2 py-1">
                            <i class="bi bi-envelope text-primary"></i>
                            <span class="small">
                                @if($plan->max_emails_per_month)
                                    {{ number_format($plan->max_emails_per_month) }} emails/month
                                @else
                                    Unlimited emails
                                @endif
                            </span>
                        </li>
                        <li class="d-flex align-items-center gap-2 py-1">
                            <i class="bi bi-person-badge text-primary"></i>
                            <span class="small">
                                @if($plan->max_users)
                                    Up to {{ $plan->max_users }} {{ Str::plural('user', $plan->max_users) }}
                                @else
                                    Unlimited users
                                @endif
                            </span>
                        </li>
                    </ul>

                    {{-- Features List --}}
                    @if(!empty($features))
                        <ul class="list-unstyled mb-4 flex-grow-1">
                            @foreach($features as $feature)
                                <li class="d-flex align-items-start gap-2 py-1">
                                    <i class="bi bi-check-circle-fill text-success mt-1 flex-shrink-0" style="font-size:13px;"></i>
                                    <span class="small">{{ $feature }}</span>
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <div class="flex-grow-1"></div>
                    @endif

                    {{-- Subscribe Button --}}
                    @if($isCurrentPlan)
                        <button type="button" class="btn btn-primary w-100" disabled>
                            <i class="bi bi-check2 me-1"></i> Current Plan
                        </button>
                    @else
                        <form method="POST" action="{{ route('dashboard.billing.subscribe') }}">
                            @csrf
                            <input type="hidden" name="plan_id" value="{{ $plan->id }}">
                            <button type="submit" class="btn btn-outline-primary w-100"
                                    onclick="return confirm('Subscribe to the {{ $plan->name }} plan for ${{ number_format($plan->price_monthly, 2) }}/month?')">
                                <i class="bi bi-arrow-up-circle me-1"></i>
                                @if($client->plan && $plan->price_monthly > $client->plan->price_monthly)
                                    Upgrade to {{ $plan->name }}
                                @elseif($client->plan && $plan->price_monthly < $client->plan->price_monthly)
                                    Downgrade to {{ $plan->name }}
                                @else
                                    Subscribe
                                @endif
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    @empty
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center py-5 text-muted">
                    <i class="bi bi-credit-card fs-1 d-block mb-2 opacity-25"></i>
                    No plans available at the moment.
                </div>
            </div>
        </div>
    @endforelse
</div>
@endsection
