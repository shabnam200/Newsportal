<?php

namespace App\Support;

use Illuminate\Http\Response;

class ImageResizeNotice
{
    public static function aboveLimit(int $maxSize): Response
    {
        $html = <<<HTML
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Image size limit</title>
  <style>
    html, body { min-height: 100%; margin: 0; }
    body { min-height: 100vh; display: grid; place-items: center; color: #526778; background: #fff; font: 16px Arial, sans-serif; }
    p { margin: 0; padding: 16px; text-align: center; }
  </style>
</head>
<body><p>Maximum image size: {$maxSize} px.</p></body>
</html>
HTML;

        return response($html, 200, [
            'Content-Type' => 'text/html; charset=UTF-8',
            'Cache-Control' => 'no-store, private',
            'X-Content-Type-Options' => 'nosniff',
            'X-Image-Resize-Error' => 'size-limit',
        ]);
    }
}
