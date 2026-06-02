<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            // Tenant relational scoping - Multi-tenant data isolation and security boundary
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');

            $table->string('name', 100);
            $table->string('email', 100);
            $table->string('phone', 20);
            $table->string('password');

            // Mathematical Bitwise Permissions Mask
            // unsignedBigInteger provides 64 bits (allocating flags for up to 64 completely independent system operations)
            $table->unsignedBigInteger('permissions_mask')->default(0);

            $table->string('status', 20)->default('active'); // active, inactive
            $table->rememberToken(); // Cryptographic session persistent token
            $table->timestamps();

            // Global identity uniqueness keeps email/password login deterministic across tenants.
            $table->unique('email');
            $table->unique('phone');

            // High-velocity composite index for authentication and bitwise authorization evaluations
            $table->index(['tenant_id', 'permissions_mask', 'status']);
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};
