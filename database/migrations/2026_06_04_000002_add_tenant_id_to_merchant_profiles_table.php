<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('merchant_profiles', function (Blueprint $table) {
            $table->foreignId('tenant_id')->nullable()->after('user_id')->constrained()->onDelete('cascade');
            $table->index(['tenant_id', 'user_id'], 'merchant_profiles_tenant_user_index');
        });

        $this->backfillTenantIds();

        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE merchant_profiles ALTER COLUMN tenant_id SET NOT NULL');
        }
    }

    public function down(): void
    {
        Schema::table('merchant_profiles', function (Blueprint $table) {
            $table->dropIndex('merchant_profiles_tenant_user_index');
            $table->dropConstrainedForeignId('tenant_id');
        });
    }

    private function backfillTenantIds(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement(
                'UPDATE merchant_profiles SET tenant_id = users.tenant_id FROM users WHERE merchant_profiles.user_id = users.id'
            );

            return;
        }

        DB::statement(
            'UPDATE merchant_profiles SET tenant_id = (SELECT tenant_id FROM users WHERE users.id = merchant_profiles.user_id)'
        );
    }
};
