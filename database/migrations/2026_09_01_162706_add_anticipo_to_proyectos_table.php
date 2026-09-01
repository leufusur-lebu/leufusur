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
        Schema::table('proyectos', function (Blueprint $table) {
            $table->decimal('anticipo_monto', 12, 2)->nullable()->after('estado');
            $table->boolean('anticipo_pagado')->default(false)->after('anticipo_monto');
            $table->date('anticipo_fecha_pago')->nullable()->after('anticipo_pagado');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('proyectos', function (Blueprint $table) {
            $table->dropColumn(['anticipo_monto', 'anticipo_pagado', 'anticipo_fecha_pago']);
        });
    }
};
