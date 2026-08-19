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
        // Un gasto puede pertenecer a una cotización, a un proyecto directo, o a
        // ninguno (gasto general de la empresa). Como máximo uno de los dos está fijado.
        Schema::table('gastos', function (Blueprint $table) {
            $table->foreignId('proyecto_id')->nullable()->after('cotizacion_id')->constrained('proyectos')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('gastos', function (Blueprint $table) {
            $table->dropConstrainedForeignId('proyecto_id');
        });
    }
};
