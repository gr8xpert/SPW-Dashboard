<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\TemplateFolder;
use Illuminate\Http\Request;

class TemplateFolderController extends Controller
{
    public function index()
    {
        $folders = TemplateFolder::withCount('templates')
            ->orderBy('name')
            ->get();

        return response()->json($folders);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:100',
        ]);

        $folder = TemplateFolder::create($data);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'folder'  => $folder,
                'message' => 'Folder created.',
            ]);
        }

        return back()->with('success', 'Folder created.');
    }

    public function update(Request $request, TemplateFolder $folder)
    {
        $data = $request->validate([
            'name' => 'required|string|max:100',
        ]);

        $folder->update($data);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'folder'  => $folder,
                'message' => 'Folder renamed.',
            ]);
        }

        return back()->with('success', 'Folder renamed.');
    }

    public function destroy(TemplateFolder $folder)
    {
        // Move templates to "no folder" before deleting
        $folder->templates()->update(['folder_id' => null]);
        $folder->delete();

        if (request()->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Folder deleted. Templates moved to root.',
            ]);
        }

        return back()->with('success', 'Folder deleted. Templates moved to root.');
    }
}
