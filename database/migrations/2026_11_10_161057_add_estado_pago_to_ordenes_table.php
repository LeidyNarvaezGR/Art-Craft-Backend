<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ordenes', function (Blueprint $table) {
            $table->string('estado_pago')
                  ->default('pendiente')
                  ->after('metodo_pago');

            $table->timestamp('fecha_pago')
                  ->nullable()
                  ->after('estado_pago');
        });
    }

    public function down(): void
    {
        Schema::table('ordenes', function (Blueprint $table) {
            $table->dropColumn(['estado_pago', 'fecha_pago']);
        });
    }
};