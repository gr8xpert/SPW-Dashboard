<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GlobalSuppression;
use Illuminate\Http\Request;

class GlobalSuppressionController extends Controller
{
    public function index(Request $request)
    {
        $query = GlobalSuppression::query();

        if ($request->search) {
            $query->where('email', 'like', "%{$request->search}%");
        }
        if ($request->reason) {
            $query->where('reason', $request->reason);
        }

        $suppressions = $query->latest('added_at')->paginate(50);
        return view('admin.suppressions.index', compact('suppressions'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'email'  => 'required|email',
            'reason' => 'required|in:hard_bounce,complaint,spam_trap,manual',
        ]);

        GlobalSuppression::updateOrCreate(
            ['email' => strtolower($request->email)],
            ['reason' => $request->reason]
        );

        return back()->with('success', 'Email added to global suppression list.');
    }

    public function destroy(int $id)
    {
        GlobalSuppression::findOrFail($id)->delete();
        return back()->with('success', 'Removed from suppression list.');
    }
}
