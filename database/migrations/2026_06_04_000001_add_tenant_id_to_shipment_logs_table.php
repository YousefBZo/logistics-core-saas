<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shipment_logs', function (Blueprint $table) {
            $table->foreignId('tenant_id')->nullable()->constrained()->onDelete('cascade');
            $table->index(['tenant_id', 'shipment_id', 'created_at'], 'shipment_logs_tenant_shipment_created_index');
        });

        $this->backfillTenantIds();

        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE shipment_logs ALTER COLUMN tenant_id SET NOT NULL');
        }
    }

    public function down(): void
    {
        Schema::table('shipment_logs', function (Blueprint $table) {
            $table->dropIndex('shipment_logs_tenant_shipment_created_index');
            $table->dropConstrainedForeignId('tenant_id');
        });
    }

    private function backfillTenantIds(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement(
                'UPDATE shipment_logs SET tenant_id = shipments.tenant_id FROM shipments WHERE shipment_logs.shipment_id = shipments.id'
            );

            return;
        }

        DB::statement(
            'UPDATE shipment_logs SET tenant_id = (SELECT tenant_id FROM shipments WHERE shipments.id = shipment_logs.shipment_id)'
        );
    }
};
