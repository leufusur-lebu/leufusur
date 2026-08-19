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
        // Una factura de venta pertenece a una cotización o a un proyecto directo (una u otra).
        Schema::table('facturas_venta', function (Blueprint $table) {
            $table->foreignId('cotizacion_id')->nullable()->change();
            $table->foreignId('proyecto_id')->nullable()->unique()->after('cotizacion_id')->constrained('proyectos')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('facturas_venta', function (Blueprint $table) {
            $table->dropConstrainedForeignId('proyecto_id');
            $table->foreignId('cotizacion_id')->nullable(false)->change();
        });
    }
};
