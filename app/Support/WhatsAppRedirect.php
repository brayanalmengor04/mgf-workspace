<?php

namespace App\Support;

class WhatsAppRedirect
{
    public static function normalizePanamaPhone(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?? '';

        if (str_starts_with($digits, '507')) {
            return $digits;
        }

        return '507'.ltrim($digits, '0');
    }

    public static function webUrl(string $digits, string $text): string
    {
        return 'https://wa.me/'.$digits.'?text='.rawurlencode($text);
    }

    public static function appUrl(string $digits, string $text): string
    {
        return 'whatsapp://send?phone='.$digits.'&text='.rawurlencode($text);
    }

    /**
     * @return array{web: string, app: string}
     */
    public static function links(string $phone, string $text): array
    {
        $digits = self::normalizePanamaPhone($phone);

        return [
            'web' => self::webUrl($digits, $text),
            'app' => self::appUrl($digits, $text),
        ];
    }

    /**
     * @param  array{web: string, app: string, pdf_url: string, filename: string, text: string}  $payload
     */
    public static function shareDocumentScript(array $payload): string
    {
        $pdfUrl = json_encode($payload['pdf_url'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $filename = json_encode($payload['filename'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $text = json_encode($payload['text'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $web = json_encode($payload['web'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $app = json_encode($payload['app'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        return <<<JS
(async function () {
    const pdfUrl = {$pdfUrl};
    const filename = {$filename};
    const text = {$text};
    const web = {$web};
    const app = {$app};

    async function trySharePdfFile() {
        if (!navigator.share) {
            return false;
        }

        try {
            const response = await fetch(pdfUrl);
            if (!response.ok) {
                return false;
            }

            const blob = await response.blob();
            const file = new File([blob], filename, { type: 'application/pdf' });
            const shareData = { files: [file], text: text };

            if (navigator.canShare && !navigator.canShare(shareData)) {
                return false;
            }

            await navigator.share(shareData);

            return true;
        } catch (error) {
            return false;
        }
    }

    const shared = await trySharePdfFile();
    if (shared) {
        return;
    }

    const isMobile = /Android|iPhone|iPad|iPod|webOS|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
    window.location.href = isMobile ? app : app;

    if (!isMobile) {
        setTimeout(function () {
            window.location.href = web;
        }, 600);
    }
})();
JS;
    }
}
