<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_loads_fast()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/dashboard?month=6&year=2026');

        $response->assertStatus(200);
        $response->assertSee('Dashboard');
    }

    public function test_report_puskesad_loads()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/reports/puskesad?month=6&year=2026');

        $response->assertStatus(200);
        $response->assertSee('Puskesad');
    }

    public function test_report_rl34_loads()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/reports/rl34?month=6&year=2026');

        $response->assertStatus(200);
        $response->assertSee('RL 3.4');
    }

    public function test_report_rl35_loads()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/reports/rl35?month=6&year=2026');

        $response->assertStatus(200);
        $response->assertSee('RL 3.5');
    }

    public function test_excel_export_puskesad_works()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/reports/puskesad/export?month=6&year=2026&poli=SEMUA');

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }
}
