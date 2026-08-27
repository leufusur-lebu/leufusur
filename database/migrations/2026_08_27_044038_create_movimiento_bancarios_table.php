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
        Schema::create('movimientos_bancarios', function (Blueprint $table) {
            $table->id();
            $table->date('fecha');
            $table->string('glosa');
            $table->string('tipo'); // abono (ingreso) | cargo (egreso)
            $table->decimal('monto', 12, 2);
            $table->boolean('conciliado')->default(false);
            // Enlace opcional al registro del sistema que corresponde (factura de venta,
            // gasto o sueldo). Polimórfico: no lleva restricción de clave foránea.
            $table->nullableMorphs('conciliable');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('movimientos_bancarios');
    }
};
