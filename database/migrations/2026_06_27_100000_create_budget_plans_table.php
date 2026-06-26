<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('budget_plans', function (Blueprint $table) {
            $table->id();
            $table->string('budget_number')->unique();
            $table->string('status')->default('draft');
            $table->string('title');
            $table->string('subtitle')->nullable();
            $table->string('period')->default('biweekly');
            $table->decimal('net_income', 12, 2)->default(0);
            $table->string('income_notes')->nullable();
            $table->string('currency', 3)->default('PAB');
            $table->decimal('total_allocated', 12, 2)->default(0);
            $table->decimal('remaining_balance', 12, 2)->default(0);
            $table->text('footer_notes')->nullable();
            $table->json('generated_payload')->nullable();
            $table->string('pdf_path')->nullable();
            $table->timestamp('issued_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('budget_plans');
    }
};
