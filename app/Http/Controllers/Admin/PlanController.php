<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use Illuminate\Http\Request;

class PlanController extends Controller
{
    public function index()
    {
        $plans = Plan::orderBy('sort_order')->get();
        return view('admin.plans.index', compact('plans'));
    }

    public function create()
    {
        return view('admin.plans.form', ['plan' => new Plan()]);
    }

    public function store(Request $request)
    {
        $data = $this->validatePlan($request);
        Plan::create($data);
        return redirect()->route('admin.plans.index')->with('success', 'Plan created successfully.');
    }

    public function edit(Plan $plan)
    {
        return view('admin.plans.form', compact('plan'));
    }

    public function update(Request $request, Plan $plan)
    {
        $data = $this->validatePlan($request);
        $plan->update($data);
        return redirect()->route('admin.plans.index')->with('success', 'Plan updated successfully.');
    }

    public function destroy(Plan $plan)
    {
        if ($plan->clients()->count() > 0) {
            return back()->with('error', 'Cannot delete plan that has active clients.');
        }
        $plan->delete();
        return redirect()->route('admin.plans.index')->with('success', 'Plan deleted.');
    }

    protected function validatePlan(Request $request): array
    {
        return $request->validate([
            'name'                  => 'required|string|max:50',
            'slug'                  => 'required|string|max:50',
            'max_contacts'          => 'required|integer|min:0',
            'max_emails_per_month'  => 'required|integer|min:0',
            'max_templates'         => 'required|integer|min:0',
            'max_users'             => 'required|integer|min:1',
            'max_image_storage_mb'  => 'required|integer|min:0',
            'price_monthly'         => 'required|numeric|min:0',
            'price_yearly'          => 'required|numeric|min:0',
            'is_active'             => 'boolean',
            'sort_order'            => 'integer',
            'features'              => 'required|array',
        ]);
    }
}
