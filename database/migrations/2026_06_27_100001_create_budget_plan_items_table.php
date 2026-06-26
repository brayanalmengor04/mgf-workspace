<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('budget_plan_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('budget_plan_id')->constrained()->cascadeOnDelete();
            $table->string('category_type')->default('fixed_expense');
            $table->unsignedInteger('sort_order')->default(0);
            $table->string('concept');
            $table->string('notes')->nullable();
            $table->decimal('amount', 12, 2)->default(0);
            $table->decimal('percentage', 5, 1)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('budget_plan_items');
    }
};
