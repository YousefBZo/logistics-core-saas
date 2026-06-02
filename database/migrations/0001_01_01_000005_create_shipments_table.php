<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shipments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('merchant_id')->constrained('users'); // The originating dispatch merchant account
            $table->foreignId('warehouse_id')->nullable()->constrained()->onDelete('set null'); // Current physical cross-dock node locations
            $table->foreignId('driver_id')->nullable()->constrained('users')->onDelete('set null'); // Assigned field delivery courier

            // Unique tracker index via high-speed binary B-Tree lookup trees for instantaneous trace operations
            $table->string('tracking_number', 50)->unique();
            $table->string('status', 30)->default('pending'); // pending, picked_up, received_in_warehouse, out_for_delivery, delivered, cancelled, returned

            // Transient Consignee Meta (Kept flat to eliminate complex consumer join abstractions)
            $table->string('customer_name', 150);
            $table->string('customer_phone', 20);
            $table->string('city', 100);
            $table->string('area_or_zone', 100); // Scopes regional pricing matrices
            $table->text('detailed_address');
            $table->decimal('customer_latitude', 10, 8)->nullable(); // Instant direct geo-routing coordinates for driver navigation mapping
            $table->decimal('customer_longitude', 11, 8)->nullable();

            // Computational Financial Scale: Preserving a 4-decimal mantissa to secure ledger accounts from fractional loss
            $table->decimal('cod_amount', 12, 4)->default(0.0000); // Total cash payment balance required at point of collection
            $table->decimal('delivery_fees', 12, 4)->default(0.0000); // Logistical service invoice fee earned by the carrier
            $table->decimal('weight_kg', 6, 2)->default(1.00);
            $table->text('notes')->nullable();

            $table->timestamps();

            // High-efficiency composite indexes enabling real-time generation of merchant ledgers and operational KPIs
            $table->index(['tenant_id', 'status', 'created_at']);
            $table->index(['driver_id', 'status']);
            $table->index(['merchant_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shipments');
    }
};
