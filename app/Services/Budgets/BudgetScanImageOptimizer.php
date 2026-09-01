<?php

namespace App\Services\Budgets;

use GdImage;

class BudgetScanImageOptimizer
{
    public function optimize(string $absolutePath): string
    {
        if (! extension_loaded('gd')) {
            return $absolutePath;
        }

        $maxDimension = (int) config('services.budget_scan.optimize_max_dimension', 1600);
        $jpegQuality = (int) config('services.budget_scan.optimize_jpeg_quality', 82);

        if ($maxDimension <= 0) {
            return $absolutePath;
        }

        $info = @getimagesize($absolutePath);
        if ($info === false) {
            return $absolutePath;
        }

        [$width, $height, $type] = $info;
        $needsResize = max($width, $height) > $maxDimension;
        $isLargeJpeg = $type === IMAGETYPE_JPEG && filesize($absolutePath) > 500_000;

        if (! $needsResize && ! $isLargeJpeg && $type === IMAGETYPE_JPEG) {
            return $absolutePath;
        }

        $source = $this->loadImage($absolutePath, $type);
        if (! $source instanceof GdImage) {
            return $absolutePath;
        }

        $scale = $needsResize ? min(1.0, $maxDimension / max($width, $height)) : 1.0;
        $newWidth = max(1, (int) round($width * $scale));
        $newHeight = max(1, (int) round($height * $scale));

        $canvas = imagecreatetruecolor($newWidth, $newHeight);
        imagecopyresampled($canvas, $source, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
        imagedestroy($source);

        $optimizedPath = $absolutePath.'.optimized.jpg';
        imagejpeg($canvas, $optimizedPath, max(1, min(100, $jpegQuality)));
        imagedestroy($canvas);

        return is_file($optimizedPath) ? $optimizedPath : $absolutePath;
    }

    private function loadImage(string $path, int $type): ?GdImage
    {
        return match ($type) {
            IMAGETYPE_JPEG => @imagecreatefromjpeg($path) ?: null,
            IMAGETYPE_PNG => @imagecreatefrompng($path) ?: null,
            IMAGETYPE_WEBP => function_exists('imagecreatefromwebp') ? (@imagecreatefromwebp($path) ?: null) : null,
            default => null,
        };
    }
}
