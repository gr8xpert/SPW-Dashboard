<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Automation;
use App\Models\AutomationStep;
use Illuminate\Http\Request;

class AutomationController extends Controller
{
    public function index()
    {
        $automations = Automation::latest()->paginate(20);

        return view('client.automations.index', compact('automations'));
    }

    public function create()
    {
        return view('client.automations.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'         => 'required|string|max:200',
            'trigger_type' => 'required|in:contact_added,list_subscribed,tag_added,contact_updated,date_field,manual,engagement_drop',
        ]);

        $data['status'] = 'draft';
        $auto = Automation::create($data);

        return redirect()->route('dashboard.automations.edit', $auto)
            ->with('success', 'Automation created. Add steps to configure it.');
    }

    public function show(Automation $automation)
    {
        return view('client.automations.show', compact('automation'));
    }

    public function edit(Automation $automation)
    {
        return view('client.automations.edit', compact('automation'));
    }

    public function update(Request $request, Automation $automation)
    {
        $data = $request->validate([
            'name'           => 'required|string|max:200',
            'trigger_type'   => 'required|in:contact_added,list_subscribed,tag_added,contact_updated,date_field,manual,engagement_drop',
            'trigger_config' => 'nullable|array',
        ]);

        $automation->update($data);

        return back()->with('success', 'Automation saved.');
    }

    public function destroy(Automation $automation)
    {
        $automation->delete();

        return redirect()->route('dashboard.automations.index')
            ->with('success', 'Automation deleted.');
    }

    public function activate(Automation $automation)
    {
        $automation->update(['status' => 'active']);

        return back()->with('success', 'Automation activated.');
    }

    public function pause(Automation $automation)
    {
        $automation->update(['status' => 'paused']);

        return back()->with('success', 'Automation paused.');
    }

    public function addStep(Request $request, Automation $automation)
    {
        $request->validate([
            'step_type'            => 'required|in:send_email',
            'config.subject'       => 'required|string|max:500',
            'config.html'          => 'required|string',
            'config.delay_minutes' => 'nullable|integer|min:0',
        ]);

        $maxOrder = $automation->steps()->max('step_order') ?? 0;

        $automation->steps()->create([
            'step_type'  => $request->step_type,
            'step_order' => $maxOrder + 1,
            'config'     => [
                'subject'       => $request->input('config.subject'),
                'html'          => $request->input('config.html'),
                'delay_minutes' => (int) $request->input('config.delay_minutes', 0),
            ],
        ]);

        return back()->with('success', 'Step added.');
    }

    public function removeStep(Automation $automation, AutomationStep $step)
    {
        $step->delete();

        return back()->with('success', 'Step removed.');
    }
}
