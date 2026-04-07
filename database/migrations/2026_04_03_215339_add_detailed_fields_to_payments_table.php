<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->string('detail')->default('Alquiler mensual')->after('amount');
            $table->decimal('subtotal', 15, 2)->after('detail')->nullable();
            $table->decimal('credit_balance', 15, 2)->default(0)->after('subtotal');
            $table->decimal('debit_balance', 15, 2)->default(0)->after('credit_balance');
            $table->text('note')->nullable()->after('receipt_number');
            $table->decimal('total', 15, 2)->after('debit_balance')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn(['detail', 'subtotal', 'credit_balance', 'debit_balance', 'total', 'note']);
        });
    }
};
