<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('provider')->default('mercado_pago')->index();
            $table->string('provider_order_id')->nullable()->unique();
            $table->string('provider_payment_id')->nullable()->unique();
            $table->string('method')->default('pix');
            $table->string('status')->index();
            $table->string('status_detail')->nullable();
            $table->unsignedInteger('amount_cents');
            $table->uuid('idempotency_key')->unique();
            $table->uuid('refund_idempotency_key')->nullable()->unique();
            $table->longText('pix_qr_code')->nullable();
            $table->longText('pix_qr_code_base64')->nullable();
            $table->text('pix_ticket_url')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('refunded_at')->nullable();
            $table->timestamp('inventory_released_at')->nullable();
            $table->json('provider_payload')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
