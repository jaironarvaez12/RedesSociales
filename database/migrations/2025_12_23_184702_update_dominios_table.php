<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // 1) Convertir '' a NULL (clave para poder poner UNIQUE)
        DB::statement("UPDATE dominios_contenido_detalles SET job_uuid = NULL WHERE job_uuid = ''");

        // 2) Asegurar tamaño + nullable (sin depender de doctrine/dbal)
        DB::statement("ALTER TABLE dominios_contenido_detalles MODIFY job_uuid CHAR(36) NULL");

        // 3) Si existe el índice, lo borramos (evita el error 1061)
        try {
            DB::statement("ALTER TABLE dominios_contenido_detalles DROP INDEX dominios_contenido_detalles_job_uuid_unique");
        } catch (\Throwable $e) {
            // si no existe, ignorar
        }

        // 4) Crear unique
        DB::statement("ALTER TABLE dominios_contenido_detalles ADD UNIQUE dominios_contenido_detalles_job_uuid_unique (job_uuid)");
    }

    public function down(): void
    {
        try {
            DB::statement("ALTER TABLE dominios_contenido_detalles DROP INDEX dominios_contenido_detalles_job_uuid_unique");
        } catch (\Throwable $e) {}

        // opcional: volver a varchar si lo tenías así
        // DB::statement("ALTER TABLE dominios_contenido_detalles MODIFY job_uuid VARCHAR(36) NULL");
    }
};
