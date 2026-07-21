<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('budget_item_templates', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('category_type')->default('fixed_expense');
            $table->string('concept');
            $table->string('notes')->nullable();
            $table->decimal('default_amount', 12, 2)->default(0);
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['user_id', 'category_type', 'is_active']);
            $table->unique(['user_id', 'category_type', 'concept']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('budget_item_templates');
    }
};
