<?php

use App\Enums\FulfillmentStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('fulfillment_status')
                ->default(FulfillmentStatus::Pending->value)
                ->index()
                ->after('status');
            $table->string('tracking_code')->nullable()->after('fulfillment_status');
            $table->text('tracking_url')->nullable()->after('tracking_code');
            $table->text('internal_notes')->nullable()->after('tracking_url');
            $table->timestamp('preparing_at')->nullable()->after('placed_at');
            $table->timestamp('shipped_at')->nullable()->after('preparing_at');
            $table->timestamp('delivered_at')->nullable()->after('shipped_at');
            $table->timestamp('fulfillment_cancelled_at')->nullable()->after('delivered_at');
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->unsignedInteger('refunded_amount_cents')->default(0)->after('amount_cents');
        });

        Schema::table('product_variants', function (Blueprint $table) {
            $table->unsignedInteger('low_stock_threshold')->default(5)->after('stock_quantity');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex(['fulfillment_status']);
            $table->dropColumn([
                'fulfillment_status',
                'tracking_code',
                'tracking_url',
                'internal_notes',
                'preparing_at',
                'shipped_at',
                'delivered_at',
                'fulfillment_cancelled_at',
            ]);
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn('refunded_amount_cents');
        });

        Schema::table('product_variants', function (Blueprint $table) {
            $table->dropColumn('low_stock_threshold');
        });
    }
};
