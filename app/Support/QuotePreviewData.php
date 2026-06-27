<?php

namespace App\Support;

use App\Enums\QuoteCurrency;
use App\Enums\QuotePdfLayout;
use Illuminate\Support\Facades\Storage;

class QuotePreviewData
{
    /**
     * @return array<string, mixed>
     */
    public static function samplePayload(?string $currency = null): array
    {
        $currency = QuoteCurrency::resolve($currency)->value;

        $items = [
            [
                'description' => 'Desarrollo de sitio web corporativo',
                'quantity' => 1.0,
                'unit_price' => 850.0,
                'tax_rate' => 7.0,
                'tax_amount' => 59.5,
                'line_total' => 909.5,
            ],
            [
                'description' => 'Hosting y mantenimiento (12 meses)',
                'quantity' => 1.0,
                'unit_price' => 120.0,
                'tax_rate' => 7.0,
                'tax_amount' => 8.4,
                'line_total' => 128.4,
            ],
            [
                'description' => 'Capacitación al equipo (4 horas)',
                'quantity' => 4.0,
                'unit_price' => 45.0,
                'tax_rate' => 7.0,
                'tax_amount' => 12.6,
                'line_total' => 192.6,
            ],
        ];

        $subtotal = 1150.0;
        $taxAmount = 80.5;
        $total = 1230.5;

        return [
            'quote_number' => 'COT-2026-0001',
            'issued_at' => now()->toIso8601String(),
            'currency' => $currency,
            'issuer' => [
                'name' => 'Mi Empresa S.A.',
                'ruc' => '1556123456789',
                'dv' => '12',
                'has_dv' => true,
                'address' => 'Calle 50, Edificio Omega, Piso 3, Ciudad de Panamá',
                'phone' => '+507 6000-0000',
                'email' => 'ventas@miempresa.com',
            ],
            'recipient' => [
                'name' => 'Cliente Ejemplo Corp.',
                'ruc' => '1556987654321',
                'dv' => '45',
                'has_dv' => true,
                'address' => 'Av. Balboa, Torre Global, Oficina 1202',
                'phone' => '+507 6111-1111',
                'email' => 'compras@cliente.com',
            ],
            'items' => $items,
            'totals' => [
                'subtotal' => $subtotal,
                'tax_amount' => $taxAmount,
                'total' => $total,
            ],
            'footer' => [
                'bank_name' => 'Banco General',
                'bank_account_number' => '04-00-01-123456-7',
                'yappy_id' => '6000-0000',
                'notes' => 'Precios válidos por 15 días. Forma de pago: 50% anticipo, 50% contra entrega.',
            ],
        ];
    }

    public static function resolveLogoDataUri(mixed $logoPath): ?string
    {
        if (blank($logoPath)) {
            return null;
        }

        $path = is_array($logoPath) ? (reset($logoPath) ?: null) : $logoPath;

        if (! is_string($path) || $path === '') {
            return null;
        }

        if (! Storage::disk('public')->exists($path)) {
            return null;
        }

        $absolutePath = Storage::disk('public')->path($path);
        $mimeType = mime_content_type($absolutePath) ?: 'image/png';

        return 'data:'.$mimeType.';base64,'.base64_encode((string) file_get_contents($absolutePath));
    }

    public static function renderLayoutDocument(
        QuotePdfLayout $layout,
        ?string $currency = null,
        ?string $primaryColor = null,
        mixed $logoPath = null,
    ): string {
        return view($layout->view(), [
            'quote' => null,
            'payload' => self::samplePayload($currency),
            'primaryColor' => filled($primaryColor) ? $primaryColor : '#d97706',
            'logoDataUri' => self::resolveLogoDataUri($logoPath),
        ])->render();
    }
}
