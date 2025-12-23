<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('dominios_contenido_detalles', function (Blueprint $table) {
            // IMPORTANTE: nullable, sin default ''
            $table->uuid('job_uuid')->nullable()->after('modelo');
        });

        // Si por alguna razón quedó '' en algunos, lo pasamos a NULL
        DB::table('dominios_contenido_detalles')
            ->where('job_uuid', '')
            ->update(['job_uuid' => null]);

        Schema::table('dominios_contenido_detalles', function (Blueprint $table) {
            $table->unique('job_uuid', 'dominios_contenido_detalles_job_uuid_unique');
        });
    }

    public function down(): void
    {
        Schema::table('dominios_contenido_detalles', function (Blueprint $table) {
            $table->dropUnique('dominios_contenido_detalles_job_uuid_unique');
            $table->dropColumn('job_uuid');
        });
    }
};