<?php

$publicPath = realpath(__DIR__ . DIRECTORY_SEPARATOR . 'public');
$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '');
$isImage = preg_match('/\.(?:jpe?g|png|gif|webp|bmp)$/i', $uri) === 1;
$hasSize = isset($_GET['size']);
$requestedPath = $publicPath
    ? realpath($publicPath . DIRECTORY_SEPARATOR . ltrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $uri), DIRECTORY_SEPARATOR))
    : false;
$isPublicFile = $requestedPath && str_starts_with($requestedPath, $publicPath . DIRECTORY_SEPARATOR) && is_file($requestedPath);

// Laravel's development server normally serves existing files before routing
// them through the app. Forward only resized image requests to Laravel.
if ($uri !== '/' && $isPublicFile && !($isImage && $hasSize)) {
    return false;
}

require $publicPath . DIRECTORY_SEPARATOR . 'index.php';
