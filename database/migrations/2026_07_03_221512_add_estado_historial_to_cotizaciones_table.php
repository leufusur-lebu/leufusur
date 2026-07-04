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
        Schema::table('cotizaciones', function (Blueprint $table) {
            $table->timestamp('enviada_en')->nullable()->after('estado');
            $table->timestamp('aprobada_en')->nullable()->after('enviada_en');
            $table->timestamp('rechazada_en')->nullable()->after('aprobada_en');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cotizaciones', function (Blueprint $table) {
            $table->dropColumn(['enviada_en', 'aprobada_en', 'rechazada_en']);
        });
    }
};
