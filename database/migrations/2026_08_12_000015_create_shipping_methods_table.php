<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shipping_methods', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('description')->nullable();
            $table->unsignedInteger('price_cents');
            $table->unsignedInteger('free_above_cents')->nullable();
            $table->unsignedInteger('min_order_cents')->nullable();
            $table->unsignedInteger('max_order_cents')->nullable();
            $table->unsignedTinyInteger('estimated_days_min')->nullable();
            $table->unsignedTinyInteger('estimated_days_max')->nullable();
            $table->unsignedInteger('sort_order')->default(0)->index();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shipping_methods');
    }
};
