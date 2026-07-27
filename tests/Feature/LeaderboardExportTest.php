<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Models\User;
use App\Models\DailyKpiOperator;
use App\Models\MdOperatorMirror;
use App\Models\MdDepartment;
use Tests\TestCase;

class LeaderboardExportTest extends TestCase
{
    use DatabaseTransactions;

    public function test_leaderboard_page_requires_authentication(): void
    {
        $response = $this->get('/leaderboard');
        $response->assertRedirect('/login');
    }

    public function test_authenticated_user_can_access_leaderboard(): void
    {
        $user = User::where('email', 'adminbubutflange@peroniks.com')->first();
        if (!$user) {
            $this->markTestSkipped('Test user not found.');
        }

        $response = $this->actingAs($user)->get('/leaderboard');
        $response->assertStatus(200);
        $response->assertViewIs('leaderboard.index');
    }

    public function test_pdf_export_streams_file(): void
    {
        $user = User::where('email', 'adminbubutflange@peroniks.com')->first();
        if (!$user) {
            $this->markTestSkipped('Test user not found.');
        }

        // Get an existing operator from mirror or mock it
        $operator = MdOperatorMirror::first();
        $opCode = $operator ? $operator->code : 'OP001';

        // Create some KPI records in the local read-write database
        DailyKpiOperator::create([
            'kpi_date' => '2026-07-01',
            'department_code' => '404.1',
            'operator_code' => $opCode,
            'total_work_hours' => 8,
            'total_target_qty' => 100,
            'total_actual_qty' => 95,
            'kpi_percent' => 95.0,
        ]);

        $response = $this->actingAs($user)->get('/leaderboard/export/pdf?start_date=2026-07-01&end_date=2026-07-05');
        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/pdf');
    }

    public function test_excel_export_downloads_file(): void
    {
        $user = User::where('email', 'adminbubutflange@peroniks.com')->first();
        if (!$user) {
            $this->markTestSkipped('Test user not found.');
        }

        // Get an existing operator from mirror
        $operator = MdOperatorMirror::first();
        $opCode = $operator ? $operator->code : 'OP001';

        // Create some KPI records in the local read-write database
        DailyKpiOperator::create([
            'kpi_date' => '2026-07-01',
            'department_code' => '404.1',
            'operator_code' => $opCode,
            'total_work_hours' => 8,
            'total_target_qty' => 100,
            'total_actual_qty' => 95,
            'kpi_percent' => 95.0,
        ]);

        $response = $this->actingAs($user)->get('/leaderboard/export/excel?start_date=2026-07-01&end_date=2026-07-05');
        $response->assertStatus(200);
        $response->assertHeader('content-disposition', 'attachment; filename=KPI_Bubut_Leaderboard_2026-07-01_to_2026-07-05.xlsx');
    }
}
