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
        Schema::create('cotizacion_borradores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            // Null = borrador de una cotización nueva; con id = edición de una existente.
            $table->foreignId('cotizacion_id')->nullable()->constrained('cotizaciones')->cascadeOnDelete();
            $table->json('payload');
            $table->timestamps();

            $table->unique(['user_id', 'cotizacion_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cotizacion_borradores');
    }
};
