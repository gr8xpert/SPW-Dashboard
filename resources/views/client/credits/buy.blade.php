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

                    <form method="POST" action="{{ route('dashboard.credits.purchase') }}">
                        @csrf
                        <input type="hidden" name="pack_id" value="{{ $pack['id'] }}">
                        <button type="submit" class="btn {{ $isPopular ? 'btn-primary' : 'btn-outline-primary' }} w-100"
                                onclick="return confirm('Purchase {{ $pack['hours'] }} credit hours for ${{ number_format($pack['price'], 2) }}?')">
                            <i class="bi bi-cart-plus me-1"></i> Buy {{ $pack['hours'] }} Hours
                        </button>
                    </form>
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
        <p class="text-muted small mb-3">
            Need a different amount? Enter the number of hours you'd like to purchase.
        </p>
        <form method="POST" action="{{ route('dashboard.credits.purchase') }}">
            @csrf
            <input type="hidden" name="type" value="custom">
            <div class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label class="form-label fw-medium">Hours</label>
                    <div class="input-group">
                        <input type="number"
                               name="hours"
                               class="form-control @error('hours') is-invalid @enderror"
                               min="1"
                               max="100"
                               step="0.5"
                               value="{{ old('hours', 5) }}"
                               required>
                        <span class="input-group-text">hours</span>
                    </div>
                    @error('hours')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-medium">Rate</label>
                    <div class="input-group">
                        <span class="input-group-text">$</span>
                        <input type="text" class="form-control" value="{{ $hourlyRate ?? '75.00' }}" readonly>
                        <span class="input-group-text">/hr</span>
                    </div>
                </div>
                <div class="col-md-4">
                    <button type="submit" class="btn btn-primary w-100"
                            onclick="return confirm('Proceed with custom credit purchase?')">
                        <i class="bi bi-cart-plus me-1"></i> Purchase
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection
