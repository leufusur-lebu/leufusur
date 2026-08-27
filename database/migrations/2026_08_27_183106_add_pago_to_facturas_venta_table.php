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
        Schema::table('facturas_venta', function (Blueprint $table) {
            $table->boolean('pagada')->default(false)->after('archivo_pdf');
            $table->date('fecha_pago')->nullable()->after('pagada');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('facturas_venta', function (Blueprint $table) {
            $table->dropColumn(['pagada', 'fecha_pago']);
        });
    }
};
