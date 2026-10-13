<?php

namespace Tests\Feature;

use App\Models\RawVisit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_loads_fast()
    {
        $user = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($user)->get('/admin/dashboard?month=6&year=2026');

        $response->assertStatus(200);
        $response->assertSee('Dashboard');
    }

    public function test_report_puskesad_loads()
    {
        $user = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($user)->get('/admin/reports/puskesad?month=6&year=2026');

        $response->assertStatus(200);
        $response->assertSee('Puskesad');
    }

    public function test_report_rl34_loads()
    {
        $user = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($user)->get('/admin/reports/rl34?month=6&year=2026');

        $response->assertStatus(200);
        $response->assertSee('RL 3.4');
    }

    public function test_report_rl35_loads()
    {
        $user = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($user)->get('/admin/reports/rl35?month=6&year=2026');

        $response->assertStatus(200);
        $response->assertSee('RL 3.5');
    }

    public function test_excel_export_puskesad_works()
    {
        $user = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($user)->get('/admin/reports/puskesad/export?month=6&year=2026&poli=SEMUA');

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }

    public function test_excel_export_rl34_works_with_periods()
    {
        $user = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($user)->get('/reports/rl34/export?month=T2&year=2026&poli=SEMUA');

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }

    public function test_excel_export_rl35_works_with_semua()
    {
        $user = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($user)->get('/reports/rl35/export?month=SEMUA&year=SEMUA&poli=SEMUA');

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }

    public function test_puskesad_classification_rules()
    {
        // 1. Asabri masuk TNI AD
        $vAsabri = new RawVisit(['jenis_penjamin' => 'ASABRI', 'kelompok' => 'PURNAWIRAWAN']);
        $this->assertEquals('TNI AD', $vAsabri->status_puskesad);

        // 2. BPJS DINAS ke 9 kelompok
        $vAd = new RawVisit(['jenis_penjamin' => 'BPJS DINAS', 'kelompok' => 'AD']);
        $this->assertEquals('TNI AD', $vAd->status_puskesad);

        $vPnsAd = new RawVisit(['jenis_penjamin' => 'BPJS DINAS', 'kelompok' => 'PNS AD']);
        $this->assertEquals('PNS AD', $vPnsAd->status_puskesad);

        $vKelAd = new RawVisit(['jenis_penjamin' => 'BPJS DINAS', 'kelompok' => 'KEL AD']);
        $this->assertEquals('KEL AD', $vKelAd->status_puskesad);

        $vAl = new RawVisit(['jenis_penjamin' => 'BPJS DINAS', 'kelompok' => 'AL']);
        $this->assertEquals('TNI AL', $vAl->status_puskesad);

        $vPnsAl = new RawVisit(['jenis_penjamin' => 'BPJS DINAS', 'kelompok' => 'PNS AL']);
        $this->assertEquals('PNS AL', $vPnsAl->status_puskesad);

        $vKelAl = new RawVisit(['jenis_penjamin' => 'BPJS DINAS', 'kelompok' => 'KEL AL']);
        $this->assertEquals('KEL AL', $vKelAl->status_puskesad);

        $vAu = new RawVisit(['jenis_penjamin' => 'BPJS DINAS', 'kelompok' => 'AU']);
        $this->assertEquals('TNI AU', $vAu->status_puskesad);

        $vPnsAu = new RawVisit(['jenis_penjamin' => 'BPJS DINAS', 'kelompok' => 'PNS AU']);
        $this->assertEquals('PNS AU', $vPnsAu->status_puskesad);

        $vKelAu = new RawVisit(['jenis_penjamin' => 'BPJS DINAS', 'kelompok' => 'KEL AU']);
        $this->assertEquals('KEL AU', $vKelAu->status_puskesad);

        // 3. BPJS KEMENTERIAN
        $vPppkDinas = new RawVisit(['jenis_penjamin' => 'BPJS KEMENTRIAN', 'kelompok' => 'PPPK DINAS']);
        $this->assertEquals('PPPK DINAS', $vPppkDinas->status_puskesad);

        $vPppkKemen = new RawVisit(['jenis_penjamin' => 'BPJS KEMENTERIAN', 'kelompok' => 'PPPK KEMENTERIAN']);
        $this->assertEquals('PPPK KEMENTERIAN', $vPppkKemen->status_puskesad);

        $vJknKemen = new RawVisit(['jenis_penjamin' => 'BPJS KEMENTERIAN', 'kelompok' => 'PNS KEMHAN/TNI']);
        $this->assertEquals('JKN KEMENTERIAN', $vJknKemen->status_puskesad);

        // 4. BPJS POLRI
        $vPolri = new RawVisit(['jenis_penjamin' => 'BPJS POLRI', 'kelompok' => 'POLRI']);
        $this->assertEquals('POLRI', $vPolri->status_puskesad);

        $vPnsPolri = new RawVisit(['jenis_penjamin' => 'BPJS POLRI', 'kelompok' => 'PNS POLRI']);
        $this->assertEquals('PNS POLRI', $vPnsPolri->status_puskesad);

        $vKelPolri = new RawVisit(['jenis_penjamin' => 'BPJS POLRI', 'kelompok' => 'KEL POLRI']);
        $this->assertEquals('KEL POLRI', $vKelPolri->status_puskesad);

        // 5. Penjamin Lain
        $vPurna = new RawVisit(['jenis_penjamin' => 'BPJS PURNAWIRAWAN', 'kelompok' => 'AD']);
        $this->assertEquals('JKN PURNAWIRAWAN', $vPurna->status_puskesad);

        $vPbi = new RawVisit(['jenis_penjamin' => 'BPJS PBI', 'kelompok' => 'AD']);
        $this->assertEquals('PBI', $vPbi->status_puskesad);

        $vMandiri = new RawVisit(['jenis_penjamin' => 'BPJS MANDIRI', 'kelompok' => 'AD']);
        $this->assertEquals('MANDIRI', $vMandiri->status_puskesad);
    }
}
