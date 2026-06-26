<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quote_templates', function (Blueprint $table) {
            $table->string('pdf_layout', 20)->default('classic')->after('footer_notes');
        });
    }

    public function down(): void
    {
        Schema::table('quote_templates', function (Blueprint $table) {
            $table->dropColumn('pdf_layout');
        });
    }
};
