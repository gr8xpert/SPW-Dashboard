<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Services\LabelService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LabelOverrideController extends Controller
{
    public function __construct(
        protected LabelService $labelService
    ) {}

    /**
     * Display the client's label overrides page.
     */
    public function index(Request $request)
    {
        $client = Auth::user()->client;
        $language = $request->input('language', $client->default_language ?? 'en_US');
        $search = $request->input('search');

        // Get all labels with metadata (default + override info)
        $allLabels = $this->labelService->getLabelsWithMetadata($client->id, $language);

        // Filter if search provided
        if ($search) {
            $allLabels = array_filter($allLabels, function ($data, $key) use ($search) {
                return str_contains(strtolower($key), strtolower($search))
                    || str_contains(strtolower($data['current_value']), strtolower($search));
            }, ARRAY_FILTER_USE_BOTH);
        }

        // Paginate manually
        $page = $request->input('page', 1);
        $perPage = 30;
        $offset = ($page - 1) * $perPage;
        $total = count($allLabels);
        $labels = array_slice($allLabels, $offset, $perPage, true);

        $languages = $this->getAvailableLanguages();

        return view('client.labels.index', compact(
            'labels', 'language', 'languages', 'search', 'page', 'perPage', 'total'
        ));
    }

    /**
     * Update or create a label override.
     */
    public function update(Request $request)
    {
        $request->validate([
            'language' => 'required|string|max:10',
            'label_key' => 'required|string|max:100',
            'label_value' => 'required|string',
        ]);

        $client = Auth::user()->client;

        $this->labelService->setClientOverride(
            $client->id,
            $request->input('language'),
            $request->input('label_key'),
            $request->input('label_value')
        );

        return back()->with('success', "Label '{$request->input('label_key')}' updated.");
    }

    /**
     * Remove a label override (revert to default).
     */
    public function reset(Request $request)
    {
        $request->validate([
            'language' => 'required|string|max:10',
            'label_key' => 'required|string|max:100',
        ]);

        $client = Auth::user()->client;

        $this->labelService->removeClientOverride(
            $client->id,
            $request->input('language'),
            $request->input('label_key')
        );

        return back()->with('success', "Label '{$request->input('label_key')}' reset to default.");
    }

    /**
     * Get list of available languages.
     */
    protected function getAvailableLanguages(): array
    {
        return [
            'en_US' => 'English (US)',
            'en_GB' => 'English (UK)',
            'es_ES' => 'Spanish',
            'de_DE' => 'German',
            'fr_FR' => 'French',
            'it_IT' => 'Italian',
            'nl_NL' => 'Dutch',
            'pt_PT' => 'Portuguese',
            'ru_RU' => 'Russian',
            'sv_SE' => 'Swedish',
            'da_DK' => 'Danish',
            'no_NO' => 'Norwegian',
            'fi_FI' => 'Finnish',
            'pl_PL' => 'Polish',
        ];
    }
}
