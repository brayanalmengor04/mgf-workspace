<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('budget_plans', function (Blueprint $table) {
            $table->string('pdf_layout', 20)->default('classic')->after('currency');
            $table->string('primary_color', 20)->default('#0f172a')->after('pdf_layout');
        });
    }

    public function down(): void
    {
        Schema::table('budget_plans', function (Blueprint $table) {
            $table->dropColumn(['pdf_layout', 'primary_color']);
        });
    }
};
