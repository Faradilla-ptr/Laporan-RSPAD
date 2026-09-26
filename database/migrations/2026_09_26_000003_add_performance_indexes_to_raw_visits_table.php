<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('raw_visits', function (Blueprint $table) {
            $table->index(['tgl_berobat', 'poliklinik'], 'idx_tgl_poli');
            $table->index(['tgl_berobat', 'status_pasien'], 'idx_tgl_status');
            $table->index(['tgl_berobat', 'kelompok'], 'idx_tgl_kelompok');
        });
    }

    public function down(): void
    {
        Schema::table('raw_visits', function (Blueprint $table) {
            $table->dropIndex('idx_tgl_poli');
            $table->dropIndex('idx_tgl_status');
            $table->dropIndex('idx_tgl_kelompok');
        });
    }
};
