<?php

namespace App\Support;

class PdfBranding
{
    /**
     * @return array<string, string|null>
     */
    public static function viewData(?string $documentType = null): array
    {
        [$primary, $secondary] = self::brandParts();
        $appUrl = rtrim((string) config('app.url'), '/');

        return [
            'appBrand' => (string) config('app.brand'),
            'appBrandPrimary' => $primary,
            'appBrandSecondary' => $secondary,
            'appIconDataUri' => self::appIconDataUri(),
            'appIconLightDataUri' => self::appIconLightDataUri(),
            'appLogoDataUri' => self::appLogoDataUri(),
            'githubIconDataUri' => self::githubIconDataUri(),
            'developerName' => 'Brayan Almengor',
            'developerUrl' => 'https://brayanalmengordev.netlify.app/',
            'githubUrl' => 'https://github.com/brayanalmengor04',
            'pdfDocumentType' => $documentType,
            'pdfDocumentLabel' => self::documentLabel($documentType),
            'appUrl' => $appUrl,
            'appUrlDisplay' => self::displayUrl($appUrl),
        ];
    }

    public static function documentLabel(?string $documentType): string
    {
        return match ($documentType) {
            'budget' => 'Presupuesto',
            'quote' => 'Cotización',
            default => 'Documento',
        };
    }

    public static function brandToneForBackground(?string $background): string
    {
        return self::isDarkColor($background) ? 'on-dark' : 'on-light';
    }

    public static function isDarkColor(?string $color): bool
    {
        if (! is_string($color) || $color === '') {
            return false;
        }

        $hex = ltrim(trim($color), '#');

        if (strlen($hex) === 3) {
            $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
        }

        if (! preg_match('/^[0-9a-fA-F]{6}$/', $hex)) {
            return false;
        }

        $red = hexdec(substr($hex, 0, 2));
        $green = hexdec(substr($hex, 2, 2));
        $blue = hexdec(substr($hex, 4, 2));

        $luminance = (0.2126 * $red + 0.7152 * $green + 0.0722 * $blue) / 255;

        return $luminance < 0.55;
    }

    /**
     * @return array{0: string, 1: string|null}
     */
    public static function brandParts(): array
    {
        $brand = trim((string) config('app.brand'));

        if ($brand === '') {
            return ['MGF', 'Workspace'];
        }

        $segments = preg_split('/\s+/', $brand, 2) ?: [];

        return [
            $segments[0],
            $segments[1] ?? null,
        ];
    }

    public static function appIconDataUri(): ?string
    {
        return self::dataUriFromPublic('images/brand/mgf-pdf-icon.svg');
    }

    public static function appIconLightDataUri(): ?string
    {
        return self::dataUriFromPublic('images/brand/mgf-pdf-icon-light.svg')
            ?? self::appIconDataUri();
    }

    public static function appLogoDataUri(): ?string
    {
        return self::dataUriFromPublic('images/brand/mgf-pdf-wordmark.svg')
            ?? self::appIconDataUri()
            ?? self::dataUriFromPublic('images/brand/mgf-logo.svg');
    }

    public static function githubIconDataUri(): string
    {
        $svg = <<<'SVG'
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="#24292f"><path fill-rule="evenodd" clip-rule="evenodd" d="M12 2C6.477 2 2 6.484 2 12.017c0 4.425 2.865 8.18 6.839 9.504.5.092.682-.217.682-.483 0-.237-.008-.868-.013-1.703-2.782.605-3.369-1.343-3.369-1.343-.454-1.158-1.11-1.466-1.11-1.466-.908-.62.069-.608.069-.608 1.003.07 1.531 1.032 1.531 1.032.892 1.53 2.341 1.088 2.91.832.092-.647.35-1.088.636-1.338-2.22-.253-4.555-1.113-4.555-4.951 0-1.093.39-1.988 1.029-2.688-.103-.253-.446-1.272.098-2.65 0 0 .84-.27 2.75 1.026A9.564 9.564 0 0112 6.844c.85.004 1.705.115 2.504.337 1.909-1.296 2.747-1.027 2.747-1.027.546 1.379.202 2.398.1 2.651.64.7 1.028 1.595 1.028 2.688 0 3.848-2.339 4.695-4.566 4.943.359.309.678.92.678 1.855 0 1.338-.012 2.419-.012 2.747 0 .268.18.58.688.482A10.019 10.019 0 0022 12.017C22 6.484 17.522 2 12 2z"/></svg>
SVG;

        return 'data:image/svg+xml;base64,'.base64_encode($svg);
    }

    private static function displayUrl(string $appUrl): string
    {
        $host = parse_url($appUrl, PHP_URL_HOST);

        if (is_string($host) && $host !== '') {
            return $host;
        }

        return $appUrl;
    }

    private static function dataUriFromPublic(string $relativePath): ?string
    {
        $absolutePath = public_path($relativePath);

        if (! is_file($absolutePath)) {
            return null;
        }

        $mimeType = mime_content_type($absolutePath) ?: 'image/png';

        return 'data:'.$mimeType.';base64,'.base64_encode((string) file_get_contents($absolutePath));
    }
}
