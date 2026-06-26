<?php

namespace Database\Seeders;

use App\Models\QuoteTemplate;
use Illuminate\Database\Seeder;

class QuoteTemplateSeeder extends Seeder
{
    public function run(): void
    {
        QuoteTemplate::query()->updateOrCreate(
            ['name' => 'Plantilla demo Panamá'],
            [
                'is_default' => true,
                'is_active' => true,
                'issuer_name' => 'Mi Empresa S.A.',
                'issuer_ruc' => '155612345',
                'issuer_dv' => '12',
                'issuer_has_dv' => true,
                'issuer_address' => 'Ciudad de Panamá, Panamá',
                'issuer_phone' => '+507 6000-0000',
                'issuer_email' => 'contacto@miempresa.com',
                'bank_name' => 'Banco General',
                'bank_account_number' => '01234567890123456789',
                'yappy_id' => '6000-0000',
                'footer_notes' => 'Cotización válida por 15 días. Precios sujetos a cambio sin previo aviso.',
                'pdf_layout' => 'classic',
                'primary_color' => '#d97706',
            ]
        );
    }
}
