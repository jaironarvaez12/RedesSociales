<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('dominios', function (Blueprint $table) {
            $table->string('elementor_template_path', 255)->nullable()->after('password');
        });
    }

    public function down(): void
    {
        Schema::table('dominios', function (Blueprint $table) {
            $table->dropColumn('elementor_template_path');
        });
    }
};
