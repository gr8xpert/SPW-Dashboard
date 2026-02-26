@extends('layouts.client')

@section('title', 'Buy Credit Hours')

@section('page-content')
<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h4 class="mb-1 fw-bold"><i class="bi bi-plus-circle me-2 text-primary"></i>Buy Credit Hours</h4>
        <p class="text-muted mb-0">Purchase credit hour packs for development, customization, and support work</p>
    </div>
    <a href="{{ route('dashboard.credits.index') }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i> Back to Credits
    </a>
</div>

{{-- Current Balance --}}
<div class="alert alert-info border-0 bg-info bg-opacity-10 d-flex align-items-center mb-4">
    <i class="bi bi-wallet2 me-2 fs-5"></i>
    <div>
        Your current balance: <strong>{{ number_format($balance ?? 0, 1) }} hours</strong>
    </div>
</div>

{{-- Credit Packs --}}
<div class="row g-4 mb-4">
    @forelse($packs ?? [] as $pack)
        @php
            $isPopular = $pack['popular'] ?? false;
        @endphp
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100 {{ $isPopular ? 'border border-2 border-primary' : '' }}"
                 style="{{ $isPopular ? 'border-color: var(--bs-primary) !important;' : '' }}">

                @if($isPopular)
                    <div class="card-header bg-primary text-white text-center py-2 rounded-top">
                        <small class="fw-bold text-uppercase"><i class="bi bi-star-fill me-1"></i> Most Popular</small>
                    </div>
                @endif

                <div class="card-body p-4 d-flex flex-column text-center">
                    <div class="mb-3">
                        <div class="rounded-circle bg-primary bg-opacity-10 d-inline-flex align-items-center justify-content-center mb-3" style="width: 64px; height: 64px;">
                            <i class="bi bi-clock fs-3 text-primary"></i>
                        </div>
                        <h5 class="fw-bold mb-1">{{ $pack['name'] }}</h5>
                        <div class="d-flex align-items-baseline justify-content-center gap-1">
                            <span class="fs-2 fw-bold text-primary">${{ number_format($pack['price'], 0) }}</span>
                        </div>
                        <div class="text-muted small">{{ $pack['hours'] }} hours</div>
                        @if($pack['hours'] > 0 && $pack['price'] > 0)
                            <div class="text-success small fw-medium">
                                ${{ number_format($pack['price'] / $pack['hours'], 2) }}/hour
                            </div>
                        @endif
                    </div>

                    <hr>

                    @if(!empty($pack['features'] ?? []))
                        <ul class="list-unstyled text-start mb-4 flex-grow-1">
                            @foreach($pack['features'] as $feature)
                                <li class="d-flex align-items-start gap-2 py-1">
                                    <i class="bi bi-check-circle-fill text-success mt-1 flex-shrink-0" style="font-size: 13px;"></i>
                                    <span class="small">{{ $feature }}</span>
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <div class="flex-grow-1"></div>
                    @endif

                    <button type="button" class="btn {{ $isPopular ? 'btn-primary' : 'btn-outline-primary' }} w-100"
                            onclick="openCreditCheckout('{{ $pack['paddle_price_id'] }}', {{ $pack['hours'] }})">
                        <i class="bi bi-cart-plus me-1"></i> Buy {{ $pack['hours'] }} Hours
                    </button>
                </div>
            </div>
        </div>
    @empty
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center py-5 text-muted">
                    <i class="bi bi-clock-history fs-1 d-block mb-2 opacity-25"></i>
                    No credit packs available at the moment.
                </div>
            </div>
        </div>
    @endforelse
</div>

{{-- Custom Amount --}}
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white border-bottom pt-4 pb-3">
        <h6 class="fw-bold mb-0"><i class="bi bi-sliders me-2 text-primary"></i>Custom Amount</h6>
    </div>
    <div class="card-body p-4">
        <div class="d-flex align-items-start gap-3">
            <div class="rounded-3 bg-info bg-opacity-10 p-3 flex-shrink-0">
                <i class="bi bi-chat-dots fs-4 text-info"></i>
            </div>
            <div>
                <p class="mb-1 fw-medium">Need a custom amount of hours?</p>
                <p class="text-muted small mb-2">
                    For custom credit hour packages, please contact our support team and we'll create a tailored package for your needs.
                </p>
                <a href="{{ route('dashboard.tickets.create') }}" class="btn btn-outline-info btn-sm">
                    <i class="bi bi-envelope me-1"></i> Contact Support
                </a>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.paddle.com/paddle/v2/paddle.js"></script>
<script>
    @if($paddleSandbox)
        Paddle.Environment.set('sandbox');
    @endif
    Paddle.Setup({ vendor: {{ (int) $paddleVendorId }} });

    function openCreditCheckout(priceId, hours) {
        if (!priceId) { alert('This pack is not yet available for purchase.'); return; }
        Paddle.Checkout.open({
            items: [{ priceId: priceId, quantity: 1 }],
            customer: { email: '{{ auth()->user()->email }}' },
            customData: {
                type: 'credit_purchase',
                client_id: {{ $client->id }},
                hours: hours,
                rate: {{ $client->credit_rate ?: config('smartmailer.credits.default_rate') }}
            }
        });
    }
</script>
@endpush
