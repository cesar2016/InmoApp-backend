<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // The original move_guarantors_to_tenants migration defined run() instead
        // of up(), so it was recorded as ran but never executed. Apply it here
        // idempotently for databases that were migrated from scratch.
        Schema::table('guarantors', function (Blueprint $table) {
            if (Schema::hasColumn('guarantors', 'contract_id')) {
                $table->dropForeign(['contract_id']);
                $table->dropColumn('contract_id');
            }
            if (! Schema::hasColumn('guarantors', 'tenant_id')) {
                $table->foreignId('tenant_id')->nullable()->constrained()->onDelete('cascade');
            }
        });
    }

    public function down(): void
    {
        Schema::table('guarantors', function (Blueprint $table) {
            if (Schema::hasColumn('guarantors', 'tenant_id')) {
                $table->dropForeign(['tenant_id']);
                $table->dropColumn('tenant_id');
            }
            if (! Schema::hasColumn('guarantors', 'contract_id')) {
                $table->foreignId('contract_id')->nullable()->constrained()->onDelete('cascade');
            }
        });
    }
};
