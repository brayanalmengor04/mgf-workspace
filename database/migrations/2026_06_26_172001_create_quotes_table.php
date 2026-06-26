<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quotes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quote_template_id')->nullable()->constrained()->nullOnDelete();
            $table->string('quote_number')->unique();
            $table->string('status')->default('draft');
            $table->string('issuer_name');
            $table->string('issuer_ruc')->nullable();
            $table->string('issuer_dv', 5)->nullable();
            $table->boolean('issuer_has_dv')->default(false);
            $table->string('issuer_address')->nullable();
            $table->string('issuer_phone')->nullable();
            $table->string('issuer_email')->nullable();
            $table->string('recipient_name');
            $table->string('recipient_ruc')->nullable();
            $table->string('recipient_dv', 5)->nullable();
            $table->boolean('recipient_has_dv')->default(false);
            $table->string('recipient_address')->nullable();
            $table->string('recipient_phone')->nullable();
            $table->string('recipient_email')->nullable();
            $table->string('bank_name')->nullable();
            $table->string('bank_account_number')->nullable();
            $table->string('yappy_id')->nullable();
            $table->text('footer_notes')->nullable();
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('tax_amount', 12, 2)->default(0);
            $table->decimal('total', 12, 2)->default(0);
            $table->json('generated_payload')->nullable();
            $table->string('pdf_path')->nullable();
            $table->timestamp('issued_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quotes');
    }
};
