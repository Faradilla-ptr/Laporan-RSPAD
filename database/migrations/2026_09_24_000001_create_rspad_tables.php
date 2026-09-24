<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('import_logs', function (Blueprint $table) {
            $table->id();
            $table->string('filename');
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->integer('period_month');
            $table->integer('period_year');
            $table->integer('total_rows')->default(0);
            $table->timestamps();
        });

        Schema::create('raw_visits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('import_log_id')->nullable()->constrained('import_logs')->onDelete('cascade');
            $table->string('no_rm')->index();
            $table->string('nama_pasien')->nullable();
            $table->string('tgl_lahir')->nullable();
            $table->string('umur')->nullable();
            $table->string('no_telp')->nullable();
            $table->string('no_hp')->nullable();
            $table->string('poliklinik')->index();
            $table->string('dokter')->nullable();
            $table->date('tgl_berobat')->index();
            $table->string('jam')->nullable();
            $table->string('no_sep')->nullable();
            $table->string('no_bpjs')->nullable();
            $table->string('status_pasien')->default('Pasien Lama'); // Pasien Baru / Pasien Lama
            $table->string('jenis_rawat')->nullable(); // WATLAN, dll
            $table->string('jenis_penjamin')->nullable(); // BPJS DINAS, BPJS PBI, ASABRI, TUNAI, dll
            $table->string('pangkat')->nullable(); // MAY, KOL, PRATU, SERDA, IIID, PENSIUNAN, dll
            $table->string('nip_nrp_pasien')->nullable();
            $table->string('gender', 10)->nullable(); // L / P
            $table->string('agama')->nullable();
            $table->string('pendidikan')->nullable();
            $table->string('kesatuan')->nullable();
            $table->string('instansi')->nullable(); // TNI, KEMENTERIAN, dll
            $table->string('kategori')->nullable(); // ORGANIK, ISTRI, ANAK, PESERTA, dll
            $table->text('alamat')->nullable();
            $table->string('icd10_utama')->nullable();
            $table->text('deskripsi_icd10_utama')->nullable();
            $table->string('icd10_sekunder')->nullable();
            $table->text('deskripsi_icd10_sekunder')->nullable();
            $table->string('status_registrasi')->default('open');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('raw_visits');
        Schema::dropIfExists('import_logs');
    }
};
