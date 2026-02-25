<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\ImageLibrary;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class ImageLibraryController extends Controller
{
    public function index()
    {
        $images = ImageLibrary::latest()->paginate(30);

        return view('client.images.index', compact('images'));
    }

    public function upload(Request $request)
    {
        $request->validate(['image' => 'required|image|max:5120']);

        $file = $request->file('image');
        $path = $file->store('images/' . Auth::user()->client_id, 'public');

        $image = ImageLibrary::create([
            'file_name' => $file->getClientOriginalName(),
            'file_path' => $path,
            'file_size' => $file->getSize(),
            'mime_type' => $file->getMimeType(),
            'url'       => Storage::url($path),
        ]);

        return response()->json(['url' => $image->url, 'id' => $image->id]);
    }

    public function destroy($id)
    {
        $image = ImageLibrary::findOrFail($id);

        Storage::disk('public')->delete($image->file_path);
        $image->delete();

        return back()->with('success', 'Image deleted.');
    }
}
