<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/** Convert uploaded raster images to WebP and store them in the requested directory. */
class WebpImageUploader
{
    public function store(UploadedFile $image, string $directory): string
    {
        if (!function_exists('imagewebp')) {
            throw ValidationException::withMessages([
                'image' => 'Image conversion to WebP is not available on this server.',
            ]);
        }

        $contents = @file_get_contents($image->getRealPath());
        $source = $contents === false ? false : @imagecreatefromstring($contents);
        if (!$source) {
            throw ValidationException::withMessages([
                'image' => 'This image could not be converted to WebP.',
            ]);
        }

        if (!imageistruecolor($source)) {
            imagepalettetotruecolor($source);
        }
        imagealphablending($source, false);
        imagesavealpha($source, true);

        $width = imagesx($source);
        $height = imagesy($source);
        $converted = imagecreatetruecolor($width, $height);
        if (!$converted) {
            imagedestroy($source);
            throw ValidationException::withMessages([
                'image' => 'This image could not be converted to WebP.',
            ]);
        }

        imagealphablending($converted, false);
        imagesavealpha($converted, true);
        imagefill($converted, 0, 0, imagecolorallocatealpha($converted, 0, 0, 0, 127));
        $copied = imagecopy($converted, $source, 0, 0, 0, 0, $width, $height);
        imagedestroy($source);

        if (!$copied) {
            imagedestroy($converted);
            throw ValidationException::withMessages([
                'image' => 'This image could not be converted to WebP.',
            ]);
        }

        File::ensureDirectoryExists($directory);
        $filename = Str::random(40) . '.webp';
        $storedPath = rtrim($directory, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $filename;
        $saved = imagewebp($converted, $storedPath, 82);
        imagedestroy($converted);

        if (!$saved) {
            File::delete($storedPath);
            throw ValidationException::withMessages([
                'image' => 'This image could not be converted to WebP.',
            ]);
        }

        return $filename;
    }
}
