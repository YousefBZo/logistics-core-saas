<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenants', function (Blueprint $table) {
            $table->id();
            $table->string('company_name', 150);
            $table->string('subdomain', 60)->unique(); // Company dedicated subdomain (e.g., tenant.domain.com)
            $table->string('logo_url')->nullable();
            $table->string('currency', 3)->default('USD'); // Local currency token supporting multi-gateway billing
            $table->string('timezone', 50)->default('UTC'); // Localized logging relative to regional logistics hubs
            $table->string('status', 20)->default('active'); // active, suspended, trialing
            $table->timestamps();

            // High-throughput query performance index
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenants');
    }
};
