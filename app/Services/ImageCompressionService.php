<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Intervention\Image\Facades\Image as InterventionImage;

class ImageCompressionService
{
    /**
     * Compress image by adjusting quality.
     *
     * @param  UploadedFile  $file
     * @param  int  $quality
     * @return UploadedFile
     */
    public function compressByQuality(UploadedFile $file, int $quality): UploadedFile
    {
        $image = InterventionImage::make($file);
        $extension = strtolower($file->getClientOriginalExtension());

        // Only compress JPEG, PNG, WebP
        if (!in_array($extension, ['jpg', 'jpeg', 'png', 'webp'])) {
            return $file;
        }

        $encoded = $image->encode($extension, $quality);
        $tempPath = tempnam(sys_get_temp_dir(), 'compress_');
        file_put_contents($tempPath, $encoded);
        $image->destroy();

        return new UploadedFile($tempPath, $file->getClientOriginalName(), $file->getMimeType(), null, true);
    }

    /**
     * Compress image by constraining maximum dimensions.
     *
     * @param  UploadedFile  $file
     * @param  int  $maxWidth
     * @param  int  $maxHeight
     * @return UploadedFile
     */
    public function compressByMaxSize(UploadedFile $file, int $maxWidth, int $maxHeight): UploadedFile
    {
        $image = InterventionImage::make($file);

        $currentWidth = $image->width();
        $currentHeight = $image->height();

        // Only resize if image exceeds both max dimensions
        if ($currentWidth <= $maxWidth && $currentHeight <= $maxHeight) {
            $image->destroy();
            return $file;
        }

        $image->resize($maxWidth, $maxHeight, function ($constraint) {
            $constraint->aspectRatio();
            $constraint->upsize();
        });

        $extension = strtolower($file->getClientOriginalExtension());
        $encoded = $image->encode($extension, 90);
        $tempPath = tempnam(sys_get_temp_dir(), 'resize_');
        file_put_contents($tempPath, $encoded);
        $image->destroy();

        return new UploadedFile($tempPath, $file->getClientOriginalName(), $file->getMimeType(), null, true);
    }

    /**
     * Convert image to target format.
     *
     * @param  UploadedFile  $file
     * @param  string  $targetFormat
     * @param  int  $quality
     * @return UploadedFile
     */
    public function convertFormat(UploadedFile $file, string $targetFormat, int $quality): UploadedFile
    {
        $image = InterventionImage::make($file);

        $targetMime = match ($targetFormat) {
            'jpg', 'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'webp' => 'image/webp',
            default => $file->getMimeType(),
        };

        $format = match ($targetFormat) {
            'jpg', 'jpeg' => 'jpg',
            'png' => 'png',
            'webp' => 'webp',
            default => strtolower($file->getClientOriginalExtension()),
        };

        $newName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME) . '.' . $format;
        $encoded = $image->encode($format, $quality);
        $tempPath = tempnam(sys_get_temp_dir(), 'convert_');
        file_put_contents($tempPath, $encoded);
        $image->destroy();

        return new UploadedFile($tempPath, $newName, $targetMime, null, true);
    }

    /**
     * Main compression entry point. Applies all compression strategies.
     *
     * @param  UploadedFile  $file
     * @param  Collection  $configs
     * @return array{file: UploadedFile, beforeSize: float, afterSize: float, mode: string}
     */
    public function compress(UploadedFile $file, Collection $configs): array
    {
        $beforeSize = $file->getSize();
        $mode = 'none';
        $extension = strtolower($file->getClientOriginalExtension());

        // Skip extensions configured to be skipped
        $skipExtensions = $configs->get('skip_extensions', ['gif', 'svg', 'ico']);
        if (in_array($extension, $skipExtensions, true)) {
            return [
                'file' => $file,
                'before_size' => $beforeSize,
                'after_size' => $beforeSize,
                'mode' => $mode,
            ];
        }

        // Skip small files below minimum file size
        $minFileSize = $configs->get('min_file_size', 10240);
        if ($beforeSize < $minFileSize) {
            return [
                'file' => $file,
                'before_size' => $beforeSize,
                'after_size' => $beforeSize,
                'mode' => $mode,
            ];
        }

        // Step 1: Resize by max dimensions
        $maxWidth = (int) $configs->get('max_width', 1920);
        $maxHeight = (int) $configs->get('max_height', 1080);
        $file = $this->compressByMaxSize($file, $maxWidth, $maxHeight);
        $mode = 'resize';

        // Step 2: Compress by quality
        $quality = (int) $configs->get('quality', 80);
        $file = $this->compressByQuality($file, $quality);
        $mode = 'quality';

        // Step 3: Convert format if specified
        $targetFormat = $configs->get('target_format', '');
        if (!empty($targetFormat) && $targetFormat !== $extension) {
            $file = $this->convertFormat($file, $targetFormat, $quality);
            $mode = 'convert';
        }

        $afterSize = $file->getSize();

        return [
            'file' => $file,
            'before_size' => $beforeSize,
            'after_size' => $afterSize,
            'mode' => $mode,
        ];
    }
}
