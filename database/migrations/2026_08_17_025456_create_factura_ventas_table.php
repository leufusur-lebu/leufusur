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
        Schema::create('facturas_venta', function (Blueprint $table) {
            $table->id();
            // Una factura de venta por proyecto: la FK es única.
            $table->foreignId('cotizacion_id')->unique()->constrained('cotizaciones')->cascadeOnDelete();
            $table->string('numero_factura');
            $table->date('fecha_emision');
            $table->string('descripcion')->nullable();
            $table->decimal('monto_neto', 12, 2);
            $table->decimal('iva', 12, 2);
            $table->decimal('total_calculado', 12, 2);
            $table->string('archivo_pdf')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('facturas_venta');
    }
};
