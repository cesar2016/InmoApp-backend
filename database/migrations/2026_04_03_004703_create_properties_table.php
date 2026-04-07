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
        Schema::create('properties', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['Casa', 'Dpto', 'Local', 'Otro']);
            $table->string('real_estate_id'); // Partida Inmobiliaria
            $table->string('domain');
            $table->string('street');
            $table->string('number');
            $table->string('floor')->nullable();
            $table->string('dept')->nullable();
            $table->string('location');
            $table->foreignId('owner_id')->constrained()->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('properties');
    }
};
