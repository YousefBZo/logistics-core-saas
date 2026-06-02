<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('merchant_profiles', function (Blueprint $table) {
            $table->id();
            // Strict One-to-One structural mapping ensuring the user possesses the explicit CREATE_SHIPMENT bit flag
            $table->foreignId('user_id')->unique()->constrained()->onDelete('cascade');
            $table->string('store_name', 150);
            $table->string('pickup_address', 255); // Physical warehouse dispatch origin where fleet drivers pick up cargo
            $table->decimal('pickup_latitude', 10, 8)->nullable();
            $table->decimal('pickup_longitude', 11, 8)->nullable();

            // Reverse API Webhook gateway for automated delivery updates back to external nodes (e.g., WooCommerce/Shopify)
            $table->string('webhook_url')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('merchant_profiles');
    }
};
