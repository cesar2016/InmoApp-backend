<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('liquidations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_id')->constrained()->onDelete('cascade');
            $table->foreignId('contract_id')->constrained()->onDelete('cascade');
            $table->string('month');
            $table->integer('year');
            $table->decimal('alquiler', 15, 2);
            $table->decimal('tasa_municipal', 15, 2)->default(0);
            $table->decimal('pago_tasa_municipal', 15, 2)->default(0);
            $table->decimal('recargo', 15, 2)->default(0);
            $table->decimal('pago_luz', 15, 2)->default(0);
            $table->decimal('descuento_admin', 15, 2)->default(0);
            $table->decimal('total_percibido', 15, 2);
            $table->decimal('total_liquidado', 15, 2);
            $table->text('note')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('liquidations');
    }
};
