<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\ImageToVideoService;

class ImageToVideoController extends Controller
{
    protected $imageToVideoService;

    public function __construct(ImageToVideoService $imageToVideoService)
    {
        $this->imageToVideoService = $imageToVideoService;
    }

    public function convertToVideo(Request $request)
    {
        $request->validate([
            'image_id' => 'required|exists:media_items,id',
            'duration' => 'required|integer|min:10|max:60',
            'music_id' => 'nullable|exists:media_items,id',
            'effects' => 'nullable|array'
        ]);

        $video = $this->imageToVideoService->convert($request->all());
        return response()->json($video);
    }

    public function index()
    {
        $videos = $this->imageToVideoService->getConvertedVideos();
        return response()->json($videos);
    }
} 