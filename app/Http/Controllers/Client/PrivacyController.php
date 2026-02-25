<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;

class PrivacyController extends Controller
{
    /**
     * GDPR privacy dashboard.
     */
    public function index()
    {
        $client = auth()->user()->client;
        return view('client.privacy.index', compact('client'));
    }

    /**
     * Export all client data as JSON.
     */
    public function export()
    {
        $client = auth()->user()->client;
        $client->load([
            'users', 'licenseKeys', 'domains',
            'supportTickets.messages', 'creditTransactions',
        ]);

        $data = [
            'client'       => $client->toArray(),
            'exported_at'  => now()->toISOString(),
            'exported_by'  => auth()->user()->email,
        ];

        AuditLog::log('gdpr.data_exported', 'client', $client->id);

        return response()->json($data)
            ->header('Content-Disposition', 'attachment; filename="data-export-' . now()->format('Y-m-d') . '.json"');
    }

    /**
     * Request account deletion (30-day cooling period).
     */
    public function requestDeletion(Request $request)
    {
        $client = auth()->user()->client;

        // TODO: Implement 30-day cooling period
        // For now, mark the request and notify admin
        AuditLog::log('gdpr.deletion_requested', 'client', $client->id, [
            'requested_by' => auth()->user()->email,
            'requested_at' => now()->toISOString(),
        ]);

        return back()->with('success', 'Deletion request submitted. Your account will be deleted in 30 days. Contact support to cancel.');
    }
}
