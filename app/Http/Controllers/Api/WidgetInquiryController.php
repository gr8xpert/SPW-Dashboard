<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Contact;
use App\Models\ContactList;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WidgetInquiryController extends Controller
{
    /**
     * POST /api/v1/widget/capture-inquiry
     *
     * Captures a property inquiry from the widget and creates/updates a contact in SmartMailer.
     */
    public function captureInquiry(Request $request): JsonResponse
    {
        $request->validate([
            'domain'       => 'required|string',
            'name'         => 'required|string|max:200',
            'email'        => 'required|email|max:320',
            'phone'        => 'nullable|string|max:50',
            'message'      => 'nullable|string|max:5000',
            'property_ref' => 'nullable|string|max:100',
            'property_title' => 'nullable|string|max:500',
        ]);

        $client = Client::where('domain', $request->domain)->first();
        if (!$client) {
            return response()->json(['error' => 'Unknown domain'], 404);
        }

        // Parse name into first/last
        $nameParts = explode(' ', $request->name, 2);
        $firstName = $nameParts[0];
        $lastName = $nameParts[1] ?? '';

        // Create or update contact
        $contact = Contact::updateOrCreate(
            ['client_id' => $client->id, 'email' => $request->email],
            [
                'first_name'    => $firstName,
                'last_name'     => $lastName,
                'phone'         => $request->phone,
                'status'        => 'subscribed',
                'source'        => 'widget_inquiry',
                'tags'          => json_encode(array_filter([
                    'widget-inquiry',
                    $request->property_ref ? 'property-' . $request->property_ref : null,
                ])),
                'custom_fields' => json_encode([
                    'last_inquiry_property' => $request->property_ref,
                    'last_inquiry_title'    => $request->property_title,
                    'last_inquiry_message'  => $request->message,
                    'last_inquiry_date'     => now()->toISOString(),
                ]),
            ]
        );

        // Add to "Widget Inquiries" list (auto-create if needed)
        $list = ContactList::firstOrCreate(
            ['client_id' => $client->id, 'name' => 'Widget Inquiries'],
            ['type' => 'static', 'description' => 'Contacts captured from widget inquiry forms']
        );

        $contact->lists()->syncWithoutDetaching([$list->id]);

        return response()->json([
            'success'    => true,
            'contact_id' => $contact->id,
        ]);
    }
}
