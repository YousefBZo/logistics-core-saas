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
            $table->string('action_type', 50)->nullable()->after('shipment_id');
            $table->foreignId('triggered_by')->nullable()->after('user_id')->constrained('users')->nullOnDelete();
            $table->index(['tenant_id', 'action_type'], 'shipment_logs_tenant_action_type_index');
        });

        DB::table('shipment_logs')
            ->whereNull('action_type')
            ->update(['action_type' => DB::raw("COALESCE(status_to, 'created')")]);

        DB::table('shipment_logs')
            ->whereNull('triggered_by')
            ->update(['triggered_by' => DB::raw('user_id')]);
    }

    public function down(): void
    {
        Schema::table('shipment_logs', function (Blueprint $table) {
            $table->dropIndex('shipment_logs_tenant_action_type_index');
            $table->dropConstrainedForeignId('triggered_by');
            $table->dropColumn('action_type');
        });
    }
};
