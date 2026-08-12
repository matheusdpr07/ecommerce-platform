<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('carts', function (Blueprint $table) {
            $table->foreignId('shipping_address_id')
                ->nullable()
                ->after('coupon_id')
                ->constrained('addresses')
                ->nullOnDelete();
            $table->foreignId('shipping_method_id')
                ->nullable()
                ->after('shipping_address_id')
                ->constrained('shipping_methods')
                ->nullOnDelete();
            $table->unsignedInteger('shipping_cents')->nullable()->after('shipping_method_id');
        });
    }

    public function down(): void
    {
        Schema::table('carts', function (Blueprint $table) {
            $table->dropConstrainedForeignId('shipping_address_id');
            $table->dropConstrainedForeignId('shipping_method_id');
            $table->dropColumn('shipping_cents');
        });
    }
};
