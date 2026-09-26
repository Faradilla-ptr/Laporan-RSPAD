<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('raw_visits', 'kelompok')) {
            Schema::table('raw_visits', function (Blueprint $table) {
                $table->string('kelompok')->nullable()->after('jenis_penjamin')->index();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('raw_visits', 'kelompok')) {
            Schema::table('raw_visits', function (Blueprint $table) {
                $table->dropColumn('kelompok');
            });
        }
    }
};
