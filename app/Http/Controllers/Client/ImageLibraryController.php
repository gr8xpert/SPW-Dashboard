<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\ImageLibrary;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ImageLibraryController extends Controller
{
    /**
     * Allowed MIME types for image uploads (validated server-side).
     */
    protected array $allowedMimes = [
        'image/jpeg',
        'image/png',
        'image/gif',
        'image/webp',
    ];

    /**
     * Allowed file extensions.
     */
    protected array $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

    public function index()
    {
        $images = ImageLibrary::latest()->paginate(30);

        return view('client.images.index', compact('images'));
    }

    public function upload(Request $request)
    {
        // SECURITY: Validate both MIME type and extension
        $request->validate([
            'image' => 'required|image|mimes:jpeg,jpg,png,gif,webp|max:5120',
        ]);

        $file = $request->file('image');

        // SECURITY: Server-side MIME type validation (don't trust client-provided type)
        $actualMime = mime_content_type($file->getPathname());
        if (!in_array($actualMime, $this->allowedMimes)) {
            return back()->withErrors(['image' => 'Invalid image type detected.']);
        }

        // SECURITY: Validate extension independently
        $extension = strtolower($file->getClientOriginalExtension());
        if (!in_array($extension, $this->allowedExtensions)) {
            return back()->withErrors(['image' => 'Invalid file extension.']);
        }

        $clientId = Auth::user()->client_id;

        // SECURITY: Generate safe random filename to prevent path traversal
        $safeFilename = Str::random(32) . '.' . $extension;
        $path = $file->storeAs('images/' . $clientId, $safeFilename, 'public');

        // Sanitize original filename for storage (remove path components)
        $originalFilename = basename($file->getClientOriginalName());
        $originalFilename = preg_replace('/[^a-zA-Z0-9._-]/', '_', $originalFilename);

        $image = ImageLibrary::create([
            'client_id'         => $clientId,
            'filename'          => $safeFilename,
            'original_filename' => Str::limit($originalFilename, 255),
            'file_path'         => $path,
            'file_size'         => $file->getSize(),
            'mime_type'         => $actualMime, // Use server-detected MIME, not client-provided
        ]);

        if ($request->expectsJson()) {
            return response()->json(['url' => Storage::url($path), 'id' => $image->id]);
        }

        return redirect()->route('dashboard.images.index')
            ->with('success', 'Image uploaded successfully.');
    }

    public function destroy($id)
    {
        $image = ImageLibrary::findOrFail($id);

        Storage::disk('public')->delete($image->file_path);
        $image->delete();

        return back()->with('success', 'Image deleted.');
    }
}
