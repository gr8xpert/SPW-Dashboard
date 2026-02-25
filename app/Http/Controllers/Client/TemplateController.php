<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Template;
use App\Models\TemplateFolder;
use App\Models\TemplateVersion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;

class TemplateController extends Controller
{
    public function index(Request $request)
    {
        $query = Template::with('folder');

        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }
        if ($request->filled('folder')) {
            $query->where('folder_id', $request->folder);
        }
        if ($request->filled('type')) {
            $query->where('editor_type', $request->type);
        }

        $templates = $query->latest()->paginate(24)->withQueryString();
        $folders = TemplateFolder::orderBy('name')->get();

        return view('client.templates.index', compact('templates', 'folders'));
    }

    public function create()
    {
        $folders = TemplateFolder::orderBy('name')->get();
        return view('client.templates.create', compact('folders'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'        => 'required|string|max:200',
            'folder_id'   => 'nullable|exists:template_folders,id',
            'mode'        => 'required|in:unlayer,html,plain',
            'html_content'       => 'nullable|string',
            'json_design'        => 'nullable|string',
            'plain_text_content' => 'nullable|string',
        ]);

        $data = $validated;
        $data['json_design'] = !empty($data['json_design']) ? json_decode($data['json_design'], true) : null;
        $data['created_by'] = Auth::id();

        $template = Template::create($data);

        // Save initial version
        TemplateVersion::create([
            'template_id'        => $template->id,
            'html_content'       => $data['html_content'] ?? null,
            'json_design'        => $data['json_design'],
            'plain_text_content' => $data['plain_text_content'] ?? null,
            'created_by'         => Auth::id(),
        ]);

        return redirect()->route('dashboard.templates.index')
            ->with('success', 'Template created.');
    }

    public function show(Template $template)
    {
        return view('client.templates.show', compact('template'));
    }

    public function edit(Template $template)
    {
        $folders = TemplateFolder::orderBy('name')->get();
        return view('client.templates.edit', compact('template', 'folders'));
    }

    public function update(Request $request, Template $template)
    {
        $validated = $request->validate([
            'name'               => 'required|string|max:200',
            'folder_id'          => 'nullable|exists:template_folders,id',
            'html_content'       => 'nullable|string',
            'json_design'        => 'nullable|string',
            'plain_text_content' => 'nullable|string',
        ]);

        $data = $validated;
        $data['json_design'] = !empty($data['json_design']) ? json_decode($data['json_design'], true) : null;

        $template->update($data);

        // Save new version
        TemplateVersion::create([
            'template_id'        => $template->id,
            'html_content'       => $data['html_content'] ?? $template->html_content,
            'json_design'        => $data['json_design'] ?? $template->json_design,
            'plain_text_content' => $data['plain_text_content'] ?? $template->plain_text_content,
            'created_by'         => Auth::id(),
        ]);

        return redirect()->route('dashboard.templates.index')
            ->with('success', 'Template saved.');
    }

    public function destroy(Template $template)
    {
        $template->delete();
        return redirect()->route('dashboard.templates.index')
            ->with('success', 'Template deleted.');
    }

    public function duplicate(Template $template)
    {
        $copy = $template->replicate();
        $copy->name = 'Copy of ' . $template->name;
        $copy->save();

        return redirect()->route('dashboard.templates.edit', $copy)
            ->with('success', 'Template duplicated.');
    }

    public function versions(Template $template)
    {
        $versions = TemplateVersion::where('template_id', $template->id)
            ->latest()->paginate(20);
        return view('client.templates.versions', compact('template', 'versions'));
    }

    public function restore(Template $template, TemplateVersion $version)
    {
        $template->update([
            'html_content'       => $version->html_content,
            'json_design'        => $version->json_design,
            'plain_text_content' => $version->plain_text_content,
        ]);
        return redirect()->route('dashboard.templates.edit', $template)
            ->with('success', 'Version restored.');
    }

    public function aiGenerate(Request $request)
    {
        $request->validate([
            'prompt'   => 'required|string|max:500',
            'tone'     => 'nullable|string',
            'industry' => 'nullable|string',
        ]);

        $apiKey = config('smartmailer.anthropic_key');
        if (!$apiKey) {
            return response()->json(['error' => 'AI generation not configured.'], 422);
        }

        try {
            $response = Http::withHeaders([
                'x-api-key'         => $apiKey,
                'anthropic-version' => '2023-06-01',
                'content-type'      => 'application/json',
            ])->post('https://api.anthropic.com/v1/messages', [
                'model'      => config('smartmailer.ai.model', 'claude-sonnet-4-6'),
                'max_tokens' => 2048,
                'messages'   => [[
                    'role'    => 'user',
                    'content' => "Generate a professional HTML email template for: {$request->prompt}. Tone: {$request->tone}. Industry: {$request->industry}. Return only clean HTML email code, no explanation.",
                ]],
            ]);

            $html = $response->json('content.0.text', '');
            return response()->json(['html' => $html]);
        } catch (\Throwable $e) {
            return response()->json(['error' => 'AI generation failed: ' . $e->getMessage()], 500);
        }
    }
}
