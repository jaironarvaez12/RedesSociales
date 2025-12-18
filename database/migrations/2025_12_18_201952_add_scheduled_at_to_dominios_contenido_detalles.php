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
       Schema::table('dominios_contenido_detalles', function (Blueprint $table) {
            $table->dateTime('scheduled_at')->nullable()->after('wp_link');
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('dominios_contenido_detalles', function (Blueprint $table) {
            //
        });
    }
};
