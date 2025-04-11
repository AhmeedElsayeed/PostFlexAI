<?php

namespace App\Http\Controllers;

use App\Models\MediaItem;
use App\Services\MediaProcessingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class MediaLibraryController extends Controller
{
    protected $mediaService;

    public function __construct(MediaProcessingService $mediaService)
    {
        $this->mediaService = $mediaService;
    }

    public function index(Request $request)
    {
        $query = MediaItem::where('team_id', Auth::user()->current_team_id);

        // Apply filters
        if ($request->has('type')) {
            $query->ofType($request->type);
        }

        if ($request->has('platform')) {
            $query->ofPlatform($request->platform);
        }

        if ($request->has('tags')) {
            $query->withTags(explode(',', $request->tags));
        }

        if ($request->has('starred')) {
            $query->starred();
        }

        if ($request->has('search')) {
            $query->search($request->search);
        }

        // Get paginated results
        $media = $query->latest()->paginate(20);

        return response()->json([
            'data' => $media,
            'meta' => [
                'total' => $media->total(),
                'per_page' => $media->perPage(),
                'current_page' => $media->currentPage(),
                'last_page' => $media->lastPage()
            ]
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required|file|max:100000', // 100MB max
            'title' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'tags' => 'nullable|array',
            'tags.*' => 'string|max:50'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $file = $request->file('file');
        $mediaItem = $this->mediaService->processUpload($file, Auth::user()->current_team_id);

        if (!$mediaItem) {
            return response()->json(['message' => 'Failed to process media file'], 500);
        }

        // Update additional fields
        $mediaItem->update([
            'title' => $request->title ?? $file->getClientOriginalName(),
            'description' => $request->description,
            'tags' => $request->tags ?? []
        ]);

        return response()->json([
            'message' => 'Media uploaded successfully',
            'data' => $mediaItem
        ], 201);
    }

    public function show(MediaItem $mediaItem)
    {
        $this->authorize('view', $mediaItem);

        return response()->json([
            'data' => $mediaItem->load('posts')
        ]);
    }

    public function update(Request $request, MediaItem $mediaItem)
    {
        $this->authorize('update', $mediaItem);

        $validator = Validator::make($request->all(), [
            'title' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'tags' => 'nullable|array',
            'tags.*' => 'string|max:50'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $mediaItem->update($request->only(['title', 'description', 'tags']));

        return response()->json([
            'message' => 'Media updated successfully',
            'data' => $mediaItem
        ]);
    }

    public function destroy(MediaItem $mediaItem)
    {
        $this->authorize('delete', $mediaItem);

        if ($this->mediaService->deleteMedia($mediaItem)) {
            return response()->json(['message' => 'Media deleted successfully']);
        }

        return response()->json(['message' => 'Failed to delete media'], 500);
    }

    public function toggleStarred(MediaItem $mediaItem)
    {
        $this->authorize('update', $mediaItem);

        $mediaItem->toggleStarred();

        return response()->json([
            'message' => 'Starred status updated successfully',
            'data' => $mediaItem
        ]);
    }

    public function addTags(Request $request, MediaItem $mediaItem)
    {
        $this->authorize('update', $mediaItem);

        $validator = Validator::make($request->all(), [
            'tags' => 'required|array',
            'tags.*' => 'string|max:50'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $mediaItem->addTags($request->tags);

        return response()->json([
            'message' => 'Tags added successfully',
            'data' => $mediaItem
        ]);
    }

    public function removeTags(Request $request, MediaItem $mediaItem)
    {
        $this->authorize('update', $mediaItem);

        $validator = Validator::make($request->all(), [
            'tags' => 'required|array',
            'tags.*' => 'string|max:50'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $mediaItem->removeTags($request->tags);

        return response()->json([
            'message' => 'Tags removed successfully',
            'data' => $mediaItem
        ]);
    }

    public function optimize(MediaItem $mediaItem)
    {
        $this->authorize('update', $mediaItem);

        if ($this->mediaService->optimizeMedia($mediaItem)) {
            return response()->json([
                'message' => 'Media optimized successfully',
                'data' => $mediaItem
            ]);
        }

        return response()->json(['message' => 'Failed to optimize media'], 500);
    }

    public function download(MediaItem $mediaItem)
    {
        $this->authorize('view', $mediaItem);

        if (!Storage::exists("public/{$mediaItem->file_path}")) {
            return response()->json(['message' => 'File not found'], 404);
        }

        return Storage::download("public/{$mediaItem->file_path}", $mediaItem->title);
    }

    public function getThumbnail(MediaItem $mediaItem)
    {
        $this->authorize('view', $mediaItem);

        if (!$mediaItem->isImage()) {
            return response()->json(['message' => 'Thumbnails only available for images'], 400);
        }

        $thumbnailPath = str_replace('.', '_thumb.', $mediaItem->file_path);

        if (!Storage::exists("public/{$thumbnailPath}")) {
            return response()->json(['message' => 'Thumbnail not found'], 404);
        }

        return Storage::response("public/{$thumbnailPath}");
    }
} 