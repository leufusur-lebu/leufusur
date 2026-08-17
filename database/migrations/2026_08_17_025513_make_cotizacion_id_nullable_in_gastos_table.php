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
        // Un gasto puede no estar ligado a una cotización (gastos generales de la
        // empresa: arriendo, servicios, etc.) que igual suman crédito fiscal al SII.
        Schema::table('gastos', function (Blueprint $table) {
            $table->foreignId('cotizacion_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('gastos', function (Blueprint $table) {
            $table->foreignId('cotizacion_id')->nullable(false)->change();
        });
    }
};
