<?php

namespace App\Services;

use App\Models\MediaItem;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Spatie\Image\Image;
use Spatie\Image\Manipulations;
use FFMpeg\FFMpeg;
use FFMpeg\Coordinate\TimeCode;
use Google\Cloud\Vision\V1\ImageAnnotatorClient;
use Illuminate\Support\Facades\Log;

class MediaProcessingService
{
    protected $allowedImageTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
    protected $allowedVideoTypes = ['video/mp4', 'video/mov', 'video/quicktime'];
    protected $allowedDocumentTypes = ['application/pdf'];
    protected $maxFileSize = 100 * 1024 * 1024; // 100MB
    protected $thumbnailSize = 300;

    public function processUpload(UploadedFile $file, int $teamId): ?MediaItem
    {
        try {
            // Validate file
            if (!$this->validateFile($file)) {
                throw new \Exception('Invalid file type or size');
            }

            // Generate unique filename
            $filename = $this->generateUniqueFilename($file);
            $path = "media/{$teamId}/{$filename}";

            // Store original file
            Storage::putFileAs("public/{$path}", $file, $filename);

            // Get file metadata
            $metadata = $this->getFileMetadata($file);

            // Create media item
            $mediaItem = MediaItem::create([
                'team_id' => $teamId,
                'title' => $file->getClientOriginalName(),
                'file_path' => $path,
                'file_type' => $this->getFileType($file->getMimeType()),
                'file_size' => $file->getSize(),
                'mime_type' => $file->getMimeType(),
                'width' => $metadata['width'] ?? null,
                'height' => $metadata['height'] ?? null,
                'duration' => $metadata['duration'] ?? null,
                'metadata' => $metadata
            ]);

            // Generate thumbnail for images
            if ($mediaItem->isImage()) {
                $this->generateThumbnail($mediaItem);
            }

            // Analyze content with AI
            $this->analyzeContent($mediaItem);

            return $mediaItem;
        } catch (\Exception $e) {
            Log::error('Media processing failed: ' . $e->getMessage());
            return null;
        }
    }

    protected function validateFile(UploadedFile $file): bool
    {
        $mimeType = $file->getMimeType();
        $size = $file->getSize();

        // Check file size
        if ($size > $this->maxFileSize) {
            return false;
        }

        // Check file type
        if (!in_array($mimeType, array_merge(
            $this->allowedImageTypes,
            $this->allowedVideoTypes,
            $this->allowedDocumentTypes
        ))) {
            return false;
        }

        return true;
    }

    protected function generateUniqueFilename(UploadedFile $file): string
    {
        $extension = $file->getClientOriginalExtension();
        return Str::uuid() . '.' . $extension;
    }

    protected function getFileType(string $mimeType): string
    {
        if (in_array($mimeType, $this->allowedImageTypes)) {
            return 'image';
        } elseif (in_array($mimeType, $this->allowedVideoTypes)) {
            return 'video';
        } elseif (in_array($mimeType, $this->allowedDocumentTypes)) {
            return 'pdf';
        }
        return 'other';
    }

    protected function getFileMetadata(UploadedFile $file): array
    {
        $metadata = [];

        if ($file->isImage()) {
            $image = Image::load($file->getPathname());
            $metadata['width'] = $image->getWidth();
            $metadata['height'] = $image->getHeight();
        } elseif ($file->isVideo()) {
            $ffmpeg = FFMpeg::create();
            $video = $ffmpeg->open($file->getPathname());
            $metadata['duration'] = $video->getDuration();
            $metadata['width'] = $video->getSize()->getWidth();
            $metadata['height'] = $video->getSize()->getHeight();
        }

        return $metadata;
    }

    protected function generateThumbnail(MediaItem $mediaItem): void
    {
        try {
            $image = Image::load(Storage::path("public/{$mediaItem->file_path}"));
            $thumbnailPath = str_replace('.', '_thumb.', $mediaItem->file_path);

            $image->fit(Manipulations::FIT_CONTAIN, $this->thumbnailSize, $this->thumbnailSize)
                ->save(Storage::path("public/{$thumbnailPath}"));
        } catch (\Exception $e) {
            Log::error('Thumbnail generation failed: ' . $e->getMessage());
        }
    }

    protected function analyzeContent(MediaItem $mediaItem): void
    {
        try {
            if ($mediaItem->isImage()) {
                $this->analyzeImage($mediaItem);
            } elseif ($mediaItem->isVideo()) {
                $this->analyzeVideo($mediaItem);
            }
        } catch (\Exception $e) {
            Log::error('Content analysis failed: ' . $e->getMessage());
        }
    }

    protected function analyzeImage(MediaItem $mediaItem): void
    {
        try {
            $imageAnnotator = new ImageAnnotatorClient();
            $image = file_get_contents(Storage::path("public/{$mediaItem->file_path}"));

            // Perform label detection
            $response = $imageAnnotator->labelDetection($image);
            $labels = $response->getLabelAnnotations();

            // Extract labels
            $aiLabels = [];
            foreach ($labels as $label) {
                $aiLabels[] = $label->getDescription();
            }

            // Update media item with AI labels
            $mediaItem->update(['ai_labels' => $aiLabels]);

            $imageAnnotator->close();
        } catch (\Exception $e) {
            Log::error('Image analysis failed: ' . $e->getMessage());
        }
    }

    protected function analyzeVideo(MediaItem $mediaItem): void
    {
        try {
            $ffmpeg = FFMpeg::create();
            $video = $ffmpeg->open(Storage::path("public/{$mediaItem->file_path}"));

            // Extract frame at 1 second for analysis
            $frame = $video->frame(TimeCode::fromSeconds(1));
            $framePath = Storage::path("public/temp/{$mediaItem->id}_frame.jpg");
            $frame->save($framePath);

            // Analyze frame
            $this->analyzeImage($mediaItem);

            // Clean up
            unlink($framePath);
        } catch (\Exception $e) {
            Log::error('Video analysis failed: ' . $e->getMessage());
        }
    }

    public function deleteMedia(MediaItem $mediaItem): bool
    {
        try {
            // Delete original file
            Storage::delete("public/{$mediaItem->file_path}");

            // Delete thumbnail if exists
            if ($mediaItem->isImage()) {
                $thumbnailPath = str_replace('.', '_thumb.', $mediaItem->file_path);
                Storage::delete("public/{$thumbnailPath}");
            }

            // Delete database record
            return $mediaItem->delete();
        } catch (\Exception $e) {
            Log::error('Media deletion failed: ' . $e->getMessage());
            return false;
        }
    }

    public function optimizeMedia(MediaItem $mediaItem): bool
    {
        try {
            if ($mediaItem->isImage()) {
                return $this->optimizeImage($mediaItem);
            } elseif ($mediaItem->isVideo()) {
                return $this->optimizeVideo($mediaItem);
            }
            return false;
        } catch (\Exception $e) {
            Log::error('Media optimization failed: ' . $e->getMessage());
            return false;
        }
    }

    protected function optimizeImage(MediaItem $mediaItem): bool
    {
        try {
            $image = Image::load(Storage::path("public/{$mediaItem->file_path}"));
            
            // Optimize image quality
            $image->optimize()
                ->save(Storage::path("public/{$mediaItem->file_path}"));

            // Update file size
            $mediaItem->update([
                'file_size' => Storage::size("public/{$mediaItem->file_path}")
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Image optimization failed: ' . $e->getMessage());
            return false;
        }
    }

    protected function optimizeVideo(MediaItem $mediaItem): bool
    {
        try {
            $ffmpeg = FFMpeg::create();
            $video = $ffmpeg->open(Storage::path("public/{$mediaItem->file_path}"));

            // Create optimized version
            $tempPath = Storage::path("public/temp/{$mediaItem->id}_optimized.mp4");
            $video->save(new \FFMpeg\Format\Video\X264(), $tempPath);

            // Replace original with optimized version
            Storage::delete("public/{$mediaItem->file_path}");
            rename($tempPath, Storage::path("public/{$mediaItem->file_path}"));

            // Update file size
            $mediaItem->update([
                'file_size' => Storage::size("public/{$mediaItem->file_path}")
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Video optimization failed: ' . $e->getMessage());
            return false;
        }
    }
} 