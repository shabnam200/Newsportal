<?php

namespace App\Http\Controllers;

use App\Services\AdImageComposer;
use App\Support\ImageResizeNotice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;

class ImageResizeController extends Controller
{
    private const MAX_WIDTH = 5000;
    private const MAX_PIXELS = 20000000;
    private const MAX_SOURCE_BYTES = 20971520;

    public function show(Request $request, string $path): BinaryFileResponse|Response
    {
        $source = $this->resolveImagePath($path);
        // AD=1: select an ad creative; if none exist, preserve the current image response.
        $adComposer = $request->query('ad') === '1' ? app(AdImageComposer::class) : null;
        $adFile = $adComposer?->pickAdImage();
        $size = $request->query('size');

        if ($size === null && $adFile === null) {
            return response()->file($source, $this->headers());
        }

        if ($size !== null && (!is_scalar($size) || !ctype_digit((string) $size) || (int) $size < 1)) {
            abort(400, 'The size parameter must be a width between 1 and ' . self::MAX_WIDTH . ' pixels.');
        }
        if ($size !== null && (int) $size > self::MAX_WIDTH) {
            return ImageResizeNotice::aboveLimit(self::MAX_WIDTH);
        }

        if (!extension_loaded('gd')) {
            abort(503, 'Image resizing requires the PHP GD extension to be enabled.');
        }

        $imageInfo = getimagesize($source);
        if ($imageInfo === false) {
            abort(415, 'The requested file is not a supported image.');
        }

        [$width, $height] = $imageInfo;
        if ($width < 1 || $height < 1 || $width * $height > self::MAX_PIXELS || filesize($source) > self::MAX_SOURCE_BYTES) {
            abort(413, 'The source image is too large to resize.');
        }

        $mime = $imageInfo['mime'] ?? '';
        $loader = match ($mime) {
            'image/jpeg' => 'imagecreatefromjpeg',
            'image/png' => 'imagecreatefrompng',
            'image/gif' => 'imagecreatefromgif',
            'image/webp' => 'imagecreatefromwebp',
            'image/bmp', 'image/x-ms-bmp' => 'imagecreatefrombmp',
            default => abort(415, 'This image format cannot be resized.'),
        };

        if (!function_exists($loader) || ($adFile === null && !function_exists('imagewebp'))) {
            abort(415, 'This image format is not supported by the installed PHP GD extension.');
        }

        $cacheDirectory = storage_path('app/image-cache');
        File::ensureDirectoryExists($cacheDirectory);
        $targetWidth = $size === null ? $width : min((int) $size, $width);
        $targetHeight = $size === null ? $height : max(1, (int) round($height * $targetWidth / $width));
        $format = match ($mime) {
            'image/jpeg' => 'jpeg',
            'image/webp' => 'webp',
            default => 'png',
        };
        $cacheKey = 'v3|' . $source . '|' . filemtime($source) . '|' . filesize($source) . '|' . $size;
        if ($adFile !== null) {
            // AD=1: use a separate cached file for this selected creative.
            $cacheKey .= '|ad-v9|' . $adFile . '|' . filemtime($adFile) . '|' . filesize($adFile);
        }
        $extension = $adFile !== null ? $format : 'webp';
        $cachePath = $cacheDirectory . DIRECTORY_SEPARATOR . hash('sha256', $cacheKey) . '.' . $extension;

        if (!is_file($cachePath)) {
            if ($adFile !== null) {
                try {
                    // AD=1: return a composed response without changing the source file.
                    $geometry = [0, 0, $width, $height, $targetWidth, $targetHeight];
                    $contents = $adComposer->compose($source, $adFile, $geometry, $format);
                } catch (\RuntimeException $e) {
                    abort(422, $e->getMessage());
                }

                $tmp = $cachePath . '.' . bin2hex(random_bytes(4)) . '.tmp';
                if (@file_put_contents($tmp, $contents) !== false && @rename($tmp, $cachePath)) {
                    return response()->file($cachePath, $this->headers($format === 'jpeg' ? 'image/jpeg' : 'image/' . $format));
                }
                @unlink($tmp);

                return response($contents, 200, $this->headers($format === 'jpeg' ? 'image/jpeg' : 'image/' . $format));
            }

            $sourceImage = $loader($source);
            if ($sourceImage === false) {
                abort(415, 'The requested image could not be decoded.');
            }

            $targetImage = imagecreatetruecolor($targetWidth, $targetHeight);
            if ($targetImage === false) {
                imagedestroy($sourceImage);
                abort(500, 'The resized image could not be created.');
            }

            if ($mime !== 'image/jpeg') {
                imagealphablending($targetImage, false);
                imagesavealpha($targetImage, true);
                $transparent = imagecolorallocatealpha($targetImage, 0, 0, 0, 127);
                imagefill($targetImage, 0, 0, $transparent);
            }

            $copied = imagecopyresampled($targetImage, $sourceImage, 0, 0, 0, 0, $targetWidth, $targetHeight, $width, $height);
            imagedestroy($sourceImage);
            if (!$copied) {
                imagedestroy($targetImage);
                abort(500, 'The image could not be resized.');
            }

            if ($mime === 'image/jpeg' && $width >= $targetWidth * 2) {
                $sharpened = imageconvolution($targetImage, [
                    [0, -0.5, 0],
                    [-0.5, 3, -0.5],
                    [0, -0.5, 0],
                ], 1, 0);
                if (!$sharpened) {
                    imagedestroy($targetImage);
                    abort(500, 'The resized image could not be sharpened.');
                }
            }

            $saved = imagewebp($targetImage, $cachePath, 82);
            imagedestroy($targetImage);

            if (!$saved) {
                abort(500, 'The resized image could not be saved.');
            }
        }

        $responseMime = $adFile !== null ? ($format === 'jpeg' ? 'image/jpeg' : 'image/' . $format) : 'image/webp';
        return response()->file($cachePath, $this->headers($responseMime));
    }

    private function resolveImagePath(string $path): string
    {
        $publicRoot = realpath(public_path());
        $relativePath = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);

        if ($publicRoot === false || $relativePath === '' || str_contains($relativePath, "\0")) {
            abort(404);
        }

        $source = realpath($publicRoot . DIRECTORY_SEPARATOR . $relativePath);
        $publicPrefix = strtolower(rtrim($publicRoot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR);

        if ($source === false || !is_file($source) || !str_starts_with(strtolower($source), $publicPrefix)) {
            abort(404);
        }

        return $source;
    }

    private function headers(?string $mime = null): array
    {
        $headers = [
            'Cache-Control' => 'public, max-age=86400',
            'X-Content-Type-Options' => 'nosniff',
        ];

        if ($mime !== null) {
            $headers['Content-Type'] = $mime;
        }

        return $headers;
    }
}
