<?php

namespace App\Services;

use Illuminate\Validation\ValidationException;

/** Pre-generate static WebP variants so common image sizes do not resize at page load. */
class ImageVariantGenerator
{
    public function variantPath(string $sourcePath, int $size, string $fit = 'cover'): string
    {
        $suffix = $fit === 'contain' ? 'contain' : 'cover';
        $version = is_file($sourcePath)
            ? substr(sha1(filemtime($sourcePath) . '|' . filesize($sourcePath)), 0, 8)
            : 'missing';

        return pathinfo($sourcePath, PATHINFO_DIRNAME)
            . DIRECTORY_SEPARATOR
            . pathinfo($sourcePath, PATHINFO_FILENAME)
            . "-{$suffix}-{$size}-{$version}.webp";
    }

    public function generate(string $sourcePath, int $size, string $fit = 'cover'): string
    {
        if ($size < 1 || !in_array($fit, ['cover', 'contain'], true) || !is_file($sourcePath)) {
            throw new \InvalidArgumentException('A valid source image, size and fit mode are required.');
        }

        if (!function_exists('imagewebp')) {
            throw ValidationException::withMessages([
                'image' => 'Image conversion to WebP is not available on this server.',
            ]);
        }

        $contents = @file_get_contents($sourcePath);
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

        $srcW = imagesx($source);
        $srcH = imagesy($source);
        if ($srcW < 1 || $srcH < 1 || $srcW * $srcH > 20000000) {
            imagedestroy($source);
            throw ValidationException::withMessages([
                'image' => 'This image is too large to prepare for display.',
            ]);
        }

        if ($fit === 'contain') {
            $scale = $size / max($srcW, $srcH);
            $srcX = 0;
            $srcY = 0;
            $cropW = $srcW;
            $cropH = $srcH;
            $destW = max(1, (int) round($srcW * $scale));
            $destH = max(1, (int) round($srcH * $scale));
        } else {
            $cropW = $cropH = min($srcW, $srcH);
            $srcX = (int) floor(($srcW - $cropW) / 2);
            $srcY = (int) floor(($srcH - $cropH) / 2);
            $destW = $destH = $size;
        }

        $target = imagecreatetruecolor($destW, $destH);
        if (!$target) {
            imagedestroy($source);
            throw ValidationException::withMessages([
                'image' => 'This image could not be prepared for display.',
            ]);
        }

        imagealphablending($target, false);
        imagesavealpha($target, true);
        imagefill($target, 0, 0, imagecolorallocatealpha($target, 0, 0, 0, 127));
        $copied = imagecopyresampled($target, $source, 0, 0, $srcX, $srcY, $destW, $destH, $cropW, $cropH);
        imagedestroy($source);

        $variantPath = $this->variantPath($sourcePath, $size, $fit);
        $saved = $copied && imagewebp($target, $variantPath, 82);
        imagedestroy($target);

        if (!$saved) {
            @unlink($variantPath);
            throw ValidationException::withMessages([
                'image' => 'This image could not be prepared for display.',
            ]);
        }

        return $variantPath;
    }
}
