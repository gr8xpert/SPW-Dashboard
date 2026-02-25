<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\BrandKit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BrandKitController extends Controller
{
    public function edit()
    {
        $kit = BrandKit::firstOrCreate(
            ['client_id' => Auth::user()->client_id],
            ['client_id' => Auth::user()->client_id]
        );

        return view('client.brand-kit.edit', compact('kit'));
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'primary_color'   => 'nullable|string|max:7',
            'secondary_color' => 'nullable|string|max:7',
            'accent_color'    => 'nullable|string|max:7',
            'font_body'       => 'nullable|string|max:100',
            'font_heading'    => 'nullable|string|max:100',
            'logo_url'        => 'nullable|url|max:500',
            'footer_html'     => 'nullable|string|max:2000',
            'company_address' => 'nullable|string|max:500',
            'social_links'    => 'nullable|array',
        ]);

        $kit = BrandKit::firstOrCreate(['client_id' => Auth::user()->client_id]);
        $kit->update($data);

        return back()->with('success', 'Brand kit saved.');
    }
}
