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
        Schema::create('gastos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cotizacion_id')->constrained('cotizaciones')->cascadeOnDelete();
            $table->date('fecha_gasto');
            $table->string('tipo');
            $table->string('numero_documento');
            $table->string('proveedor');
            $table->string('descripcion');
            $table->decimal('monto_neto', 12, 2);
            $table->decimal('iva', 12, 2);
            $table->decimal('total_calculado', 12, 2);
            $table->string('archivo_comprobante')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gastos');
    }
};
