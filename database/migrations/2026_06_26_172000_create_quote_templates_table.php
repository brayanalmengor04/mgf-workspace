<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quote_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->string('issuer_name');
            $table->string('issuer_ruc')->nullable();
            $table->string('issuer_dv', 5)->nullable();
            $table->boolean('issuer_has_dv')->default(false);
            $table->string('issuer_address')->nullable();
            $table->string('issuer_phone')->nullable();
            $table->string('issuer_email')->nullable();
            $table->string('bank_name')->nullable();
            $table->string('bank_account_number')->nullable();
            $table->string('yappy_id')->nullable();
            $table->text('footer_notes')->nullable();
            $table->string('logo_path')->nullable();
            $table->string('primary_color', 7)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quote_templates');
    }
};
