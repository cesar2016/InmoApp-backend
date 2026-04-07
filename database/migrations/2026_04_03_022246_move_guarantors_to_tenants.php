<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function run(): void
    {
        Schema::table('guarantors', function (Blueprint $table) {
            if (Schema::hasColumn('guarantors', 'contract_id')) {
                $table->dropForeign('guarantors_contract_id_foreign');
                $table->dropColumn('contract_id');
            }
            if (!Schema::hasColumn('guarantors', 'tenant_id')) {
                $table->foreignId('tenant_id')->nullable()->after('id')->constrained()->onDelete('cascade');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('guarantors', function (Blueprint $table) {
            $table->foreignId('contract_id')->nullable()->constrained()->onDelete('cascade');
            $table->dropForeign(['tenant_id']);
            $table->dropColumn('tenant_id');
        });
    }
};
