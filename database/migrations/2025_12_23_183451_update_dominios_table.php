<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('dominios_contenido_detalles', function (Blueprint $table) {
            if (Schema::hasColumn('dominios_contenido_detalles', 'job_uuid')) {
                $table->uuid('job_uuid')->nullable()->change();
            } else {
                $table->uuid('job_uuid')->nullable()->after('id_dominio_contenido_detalle');
            }
        });

        DB::table('dominios_contenido_detalles')
            ->where('job_uuid', '')
            ->update(['job_uuid' => null]);

        // ❌ NO crear unique aquí porque ya existe
    }

    public function down(): void
    {
        // nada
    }
};