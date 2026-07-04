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
        Schema::create('cotizaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cliente_id')->constrained('clientes');
            $table->string('numero_cotizacion')->unique();
            $table->string('descripcion');
            $table->date('fecha_emision');
            $table->date('fecha_vencimiento');
            $table->decimal('subtotal_calculado', 12, 2)->default(0);
            $table->decimal('descuento_porcentaje', 5, 2)->default(0);
            $table->decimal('base_gravada_calculada', 12, 2)->default(0);
            $table->decimal('iva_calculado', 12, 2)->default(0);
            $table->decimal('total_calculado', 12, 2)->default(0);
            $table->string('estado')->default('borrador');
            $table->string('id_mercado_publico')->nullable();
            $table->string('archivo_adjunto')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cotizaciones');
    }
};
