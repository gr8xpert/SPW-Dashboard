@extends('layouts.admin')

@section('title', 'Plans — SmartMailer Admin')
@section('page-title', 'Subscription Plans')

@section('page-content')

@php
    $plans = isset($plans) ? $plans : \App\Models\Plan::orderBy('sort_order')->get();
@endphp

<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h4 class="mb-1 fw-bold"><i class="bi bi-tags me-2 text-primary"></i>Subscription Plans</h4>
        <p class="text-muted mb-0">Manage pricing plans available to clients</p>
    </div>
    <a href="{{ route('admin.plans.create') }}" class="btn btn-primary">
        <i class="bi bi-plus-lg me-1"></i> New Plan
    </a>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        @if($plans->isEmpty())
            <div class="text-center text-muted py-5">
                <i class="bi bi-tags fs-1 d-block mb-3 opacity-25"></i>
                <p class="mb-1 fw-semibold">No plans configured yet</p>
                <p class="small mb-3">Create your first subscription plan to get started.</p>
                <a href="{{ route('admin.plans.create') }}" class="btn btn-primary btn-sm">
                    <i class="bi bi-plus-lg me-1"></i> New Plan
                </a>
            </div>
        @else
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4">Name</th>
                            <th>Slug</th>
                            <th class="text-end">Price / Month</th>
                            <th class="text-end">Price / Year</th>
                            <th class="text-end">Max Contacts</th>
                            <th class="text-end">Emails / Month</th>
                            <th class="text-end">Max Users</th>
                            <th class="text-center">Active</th>
                            <th class="text-center">Sort</th>
                            <th class="text-end pe-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($plans as $plan)
                            <tr>
                                <td class="ps-4">
                                    <div class="fw-semibold">{{ $plan->name }}</div>
                                </td>
                                <td>
                                    <code class="text-muted small">{{ $plan->slug }}</code>
                                </td>
                                <td class="text-end">
                                    ${{ number_format($plan->price_monthly, 2) }}
                                </td>
                                <td class="text-end">
                                    ${{ number_format($plan->price_yearly, 2) }}
                                </td>
                                <td class="text-end">
                                    {{ $plan->max_contacts >= 999999 ? 'Unlimited' : number_format($plan->max_contacts) }}
                                </td>
                                <td class="text-end">
                                    {{ $plan->max_emails_per_month >= 999999 ? 'Unlimited' : number_format($plan->max_emails_per_month) }}
                                </td>
                                <td class="text-end">
                                    {{ $plan->max_users >= 999999 ? 'Unlimited' : number_format($plan->max_users) }}
                                </td>
                                <td class="text-center">
                                    @if($plan->is_active)
                                        <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25">
                                            <i class="bi bi-check-circle me-1"></i>Active
                                        </span>
                                    @else
                                        <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25">
                                            <i class="bi bi-x-circle me-1"></i>Inactive
                                        </span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <span class="text-muted small">{{ $plan->sort_order }}</span>
                                </td>
                                <td class="text-end pe-4">
                                    <div class="d-flex justify-content-end gap-1">
                                        <a href="{{ route('admin.plans.edit', $plan) }}"
                                           class="btn btn-sm btn-outline-primary" title="Edit">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <form method="POST" action="{{ route('admin.plans.destroy', $plan) }}"
                                              onsubmit="return confirm('Delete plan \'{{ addslashes($plan->name) }}\'? Clients on this plan will be affected.')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>

@endsection
