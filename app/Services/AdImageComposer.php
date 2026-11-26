<?php

namespace App\Services;

use GdImage;
use RuntimeException;

/**
 * AD=1: append an ad creative from public/ads below the original image.
 *
 * Final image (top to bottom):
 *   1. BASE   - the real image (requested crop/size)
 *   2. PANEL  - same width as the base, shaped like the ad but at most 16:10 tall:
 *                 a) blurred "cover" of the ad as background (only visible if the ad is taller than 16:10)
 *                 b) the sharp ad, centered, never cropped or stretched
 *
 * Everything is baked into ONE image, so "save picture" saves real image + ad together.
 */
class AdImageComposer
{
    private const MAX_PIXELS = 20000000;
    private const MAX_FILE_BYTES = 20 * 1024 * 1024;
    private const MAX_MEMORY = 640 * 1024 * 1024;
    private const PANEL_RATIO_W = 16;
    private const PANEL_RATIO_H = 10;
    private const BLUR_SHRINK = 5;   // blur on a 1/5 size copy: faster and smoother
    private const BLUR_PASSES = 16;

    /** Pick one supported ad creative; return null until the folder has an image. */
    public function pickAdImage(): ?string
    {
        $directory = public_path('ads');
        if (!is_dir($directory)) {
            return null;
        }

        $images = array_values(array_filter(
            glob($directory . DIRECTORY_SEPARATOR . '*') ?: [],
            static fn (string $file): bool => is_file($file)
                && in_array(strtolower(pathinfo($file, PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png', 'webp'], true)
        ));

        return $images ? $images[array_rand($images)] : null;
    }

    /**
     * $geometry is the normal image endpoint crop: [srcX, srcY, srcW, srcH, outW, outH].
     * Returns the encoded image bytes (jpeg / webp / png).
     */
    public function compose(string $sourceFile, string $adFile, array $geometry, string $format): string
    {
        [$cropX, $cropY, $cropW, $cropH, $baseW, $baseH] = $geometry;
        if ($baseW < 1 || $baseH < 1 || !function_exists('imagecreatefromstring')) {
            throw new RuntimeException('This image cannot be composed.');
        }

        // Step 1: check the files, then load them.
        $this->assertUsableFile($sourceFile);
        $this->assertUsableFile($adFile);
        $source = $this->load($sourceFile);
        $ad = $this->load($adFile);

        $canvas = null;
        try {
            // Step 2: check the crop and the memory needed.
            $this->assertCropInside($source, $cropX, $cropY, $cropW, $cropH);
            // Panel follows the ad's own shape (no empty space), but is never taller than 16:10.
            $maxPanelH = $baseW * self::PANEL_RATIO_H / self::PANEL_RATIO_W;
            $panelH = max(1, (int) round(min($maxPanelH, $baseW * imagesy($ad) / imagesx($ad))));
            $this->assertEnoughMemory($source, $ad, $baseW, $baseH + $panelH, $panelH);

            // Step 3: make the canvas = base + panel.
            $canvas = $this->createCanvas($baseW, $baseH + $panelH, $format);
            $this->enableAlpha($source);
            $this->enableAlpha($ad);

            // Step 4: paint the three layers.
            $this->paintBase($canvas, $source, [$cropX, $cropY, $cropW, $cropH], $baseW, $baseH);
            $this->paintPanelBackground($canvas, $ad, $baseW, $baseH, $panelH);
            $this->paintAd($canvas, $ad, $baseW, $baseH, $panelH);

            // Step 5: encode.
            return $this->encode($canvas, $format);
        } finally {
            unset($source, $ad, $canvas);
        }
    }

    /* ---------------------------------------------------------------- *
     * Painting layers
     * ---------------------------------------------------------------- */

    /** Layer 1: the real image on top. */
    private function paintBase(GdImage $canvas, GdImage $source, array $crop, int $baseW, int $baseH): void
    {
        [$x, $y, $w, $h] = $crop;
        if (!imagecopyresampled($canvas, $source, 0, 0, $x, $y, $baseW, $baseH, $w, $h)) {
            throw new RuntimeException('The original image could not be placed on the canvas.');
        }
    }

    /** Layer 2: blurred cover of the ad fills the whole 16:10 panel (no empty space, no stretching). */
    private function paintPanelBackground(GdImage $canvas, GdImage $ad, int $baseW, int $baseH, int $panelH): void
    {
        $smallW = max(1, (int) ceil($baseW / self::BLUR_SHRINK));
        $smallH = max(1, (int) ceil($panelH / self::BLUR_SHRINK));
        $small = imagecreatetruecolor($smallW, $smallH);
        if (!$small) {
            throw new RuntimeException('The combined image could not be created.');
        }

        imagefill($small, 0, 0, imagecolorallocate($small, 0, 0, 0));
        [$x, $y, $w, $h] = $this->centerCrop(imagesx($ad), imagesy($ad), $smallW / $smallH);
        imagecopyresampled($small, $ad, 0, 0, $x, $y, $smallW, $smallH, $w, $h);

        for ($pass = 0; $pass < self::BLUR_PASSES; $pass++) {
            imagefilter($small, IMG_FILTER_GAUSSIAN_BLUR);
        }

        imagecopyresampled($canvas, $small, 0, $baseH, 0, 0, $baseW, $panelH, $smallW, $smallH);
        unset($small);

        // Soft light veil so the sharp ad stands out from the blur.
        imagefilledrectangle(
            $canvas, 0, $baseH, $baseW - 1, $baseH + $panelH - 1,
            imagecolorallocatealpha($canvas, 229, 229, 229, 30)
        );
    }

    /** Layer 3: the sharp ad, centered, keeping its own aspect ratio. */
    private function paintAd(GdImage $canvas, GdImage $ad, int $baseW, int $baseH, int $panelH): void
    {
        $adW = imagesx($ad);
        $adH = imagesy($ad);
        $scale = min($baseW / $adW, $panelH / $adH);
        $showW = max(1, (int) round($adW * $scale));
        $showH = max(1, (int) round($adH * $scale));
        $left = intdiv($baseW - $showW, 2);
        $top = $baseH + intdiv($panelH - $showH, 2);

        if (!imagecopyresampled($canvas, $ad, $left, $top, 0, 0, $showW, $showH, $adW, $adH)) {
            throw new RuntimeException('The ad creative could not be appended.');
        }
    }

    /* ---------------------------------------------------------------- *
     * Helpers
     * ---------------------------------------------------------------- */

    private function createCanvas(int $width, int $height, string $format): GdImage
    {
        $canvas = imagecreatetruecolor($width, $height);
        if (!$canvas) {
            throw new RuntimeException('The combined image could not be created.');
        }

        imagealphablending($canvas, false);
        if ($this->isJpeg($format)) {
            imagefill($canvas, 0, 0, imagecolorallocate($canvas, 255, 255, 255));   // jpeg: white
        } else {
            imagesavealpha($canvas, true);
            imagefill($canvas, 0, 0, imagecolorallocatealpha($canvas, 0, 0, 0, 127)); // png/webp: transparent
        }
        imagealphablending($canvas, true);

        return $canvas;
    }

    private function encode(GdImage $canvas, string $format): string
    {
        ob_start();
        $written = match ($format) {
            'jpeg', 'jpg' => imagejpeg($canvas, null, 82),
            'webp' => function_exists('imagewebp') ? imagewebp($canvas, null, 82) : false,
            default => imagepng($canvas, null, 9),
        };
        $contents = ob_get_clean();

        if (!$written || !is_string($contents) || $contents === '') {
            throw new RuntimeException('This image format cannot be composed.');
        }

        return $contents;
    }

    private function assertUsableFile(string $file): void
    {
        $info = @getimagesize($file);
        if (filesize($file) > self::MAX_FILE_BYTES || !$info || $info[0] < 1 || $info[1] < 1
            || $info[0] * $info[1] > self::MAX_PIXELS) {
            throw new RuntimeException('The uploaded image or ad creative is too large or unsupported.');
        }
    }

    private function load(string $file): GdImage
    {
        $contents = @file_get_contents($file);
        $image = $contents === false ? false : @imagecreatefromstring($contents);
        if (!$image) {
            throw new RuntimeException('The uploaded image or ad creative could not be decoded.');
        }

        return $image;
    }

    private function assertCropInside(GdImage $source, int $x, int $y, int $w, int $h): void
    {
        if ($x < 0 || $y < 0 || $w < 1 || $h < 1 || $x + $w > imagesx($source) || $y + $h > imagesy($source)) {
            throw new RuntimeException('The original image crop is invalid.');
        }
    }

    private function assertEnoughMemory(GdImage $source, GdImage $ad, int $width, int $height, int $panelH): void
    {
        $smallW = max(1, (int) ceil($width / self::BLUR_SHRINK));
        $smallH = max(1, (int) ceil($panelH / self::BLUR_SHRINK));
        $needed = (imagesx($source) * imagesy($source) + imagesx($ad) * imagesy($ad)) * 4
            + $width * $height * 4
            + $smallW * $smallH * 4
            + memory_get_usage() + 48 * 1024 * 1024;

        if ($needed > self::MAX_MEMORY) {
            throw new RuntimeException('These images are too large to compose on this server.');
        }

        $value = trim((string) ini_get('memory_limit'));
        if ($value === '' || $value === '-1') {
            return;
        }

        $limit = $this->memoryLimitBytes($value);
        if ($limit > 0 && $needed > $limit) {
            @ini_set('memory_limit', (string) $needed);
            $limit = $this->memoryLimitBytes((string) ini_get('memory_limit'));
            if ($limit > 0 && $needed > $limit) {
                throw new RuntimeException('These images are too large for the available server memory.');
            }
        }
    }

    /** Biggest crop of $width x $height that has the given aspect ratio, centered. */
    private function centerCrop(int $width, int $height, float $aspect): array
    {
        if ($width / $height > $aspect) {
            $cropW = max(1, (int) floor($height * $aspect));
            return [intdiv($width - $cropW, 2), 0, $cropW, $height];
        }

        $cropH = max(1, (int) floor($width / $aspect));
        return [0, intdiv($height - $cropH, 2), $width, $cropH];
    }

    private function enableAlpha(GdImage $image): void
    {
        imagealphablending($image, false);
        imagesavealpha($image, true);
    }

    private function isJpeg(string $format): bool
    {
        return $format === 'jpeg' || $format === 'jpg';
    }

    private function memoryLimitBytes(string $value): int
    {
        $number = (int) $value;
        return match (strtolower(substr($value, -1))) {
            'g' => $number * 1024 ** 3,
            'm' => $number * 1024 ** 2,
            'k' => $number * 1024,
            default => $number,
        };
    }
}