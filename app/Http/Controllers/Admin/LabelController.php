<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DefaultLabel;
use App\Services\LabelService;
use Illuminate\Http\Request;

class LabelController extends Controller
{
    public function __construct(
        protected LabelService $labelService
    ) {}

    /**
     * Display the label management page.
     */
    public function index(Request $request)
    {
        $language = $request->input('language', 'en_US');
        $search = $request->input('search');

        $query = DefaultLabel::where('language', $language);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('label_key', 'like', "%{$search}%")
                  ->orWhere('label_value', 'like', "%{$search}%");
            });
        }

        $labels = $query->orderBy('label_key')->paginate(50);
        $languages = $this->getAvailableLanguages();

        return view('admin.labels.index', compact('labels', 'language', 'languages', 'search'));
    }

    /**
     * Store a new label or update existing.
     */
    public function store(Request $request)
    {
        $request->validate([
            'language' => 'required|string|max:10',
            'label_key' => 'required|string|max:100',
            'label_value' => 'required|string',
        ]);

        $this->labelService->setDefaultLabel(
            $request->input('language'),
            $request->input('label_key'),
            $request->input('label_value')
        );

        return back()->with('success', "Label '{$request->input('label_key')}' saved.");
    }

    /**
     * Update a label.
     */
    public function update(Request $request, DefaultLabel $label)
    {
        $request->validate([
            'label_value' => 'required|string',
        ]);

        $label->update(['label_value' => $request->input('label_value')]);

        return back()->with('success', "Label '{$label->label_key}' updated.");
    }

    /**
     * Delete a label.
     */
    public function destroy(DefaultLabel $label)
    {
        $key = $label->label_key;
        $label->delete();

        return back()->with('success', "Label '{$key}' deleted.");
    }

    /**
     * Bulk import labels from JSON.
     */
    public function import(Request $request)
    {
        $request->validate([
            'language' => 'required|string|max:10',
            'labels_json' => 'required|json',
        ]);

        $language = $request->input('language');
        $labels = json_decode($request->input('labels_json'), true);

        if (!is_array($labels)) {
            return back()->with('error', 'Invalid JSON format.');
        }

        $count = $this->labelService->bulkImportDefaults($language, $labels);

        return back()->with('success', "Imported {$count} labels for {$language}.");
    }

    /**
     * Export labels as JSON.
     */
    public function export(Request $request)
    {
        $language = $request->input('language', 'en_US');
        $labels = DefaultLabel::getLabelsForLanguage($language);

        return response()->json($labels, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
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
