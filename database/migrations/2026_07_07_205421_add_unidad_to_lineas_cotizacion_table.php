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
        Schema::table('lineas_cotizacion', function (Blueprint $table) {
            $table->string('unidad')->default('UN')->after('descripcion');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('lineas_cotizacion', function (Blueprint $table) {
            $table->dropColumn('unidad');
        });
    }
};
