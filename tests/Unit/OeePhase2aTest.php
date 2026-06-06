<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\OeeService;
use App\Http\Controllers\OeeController;
use Illuminate\Http\Request;
use ReflectionMethod;

class OeePhase2aTest extends TestCase
{
    /**
     * Test the Future Date Clamp inside OeeController.
     */
    public function test_future_date_clamp(): void
    {
        // Mock OeeService to bypass database queries entirely during controller test
        $mockService = $this->createMock(OeeService::class);
        $mockService->method('getOeeReport')->willReturn([
            'rows' => [],
            'summary' => [],
            'row_count' => 0,
            'top_downtime_reasons' => [],
            'top_reject_reasons' => [],
        ]);
        $this->app->instance(OeeService::class, $mockService);

        $controller = new OeeController($mockService);
        $method = new ReflectionMethod(OeeController::class, 'resolveReportData');
        $method->setAccessible(true);

        // Mock request with future date
        $futureDate = date('Y-m-d', strtotime('+5 days'));
        $request = Request::create('/oee', 'GET', [
            'start_date' => date('Y-m-d'),
            'end_date' => $futureDate,
        ]);

        // Login as test user to pass auth/department check
        $user = \App\Models\User::where('email', 'adminbubutflange@peroniks.com')->first();
        $this->actingAs($user);
        session(['selected_department_code' => '404.1']);

        $result = $method->invoke($controller, $request);

        // Check that endDate was clamped to today
        $this->assertEquals(date('Y-m-d'), $result['endDate']);
    }

    /**
     * Test Performance Capping and Negative Runtime Fix under Availability V2
     * (Default Capacity fallback = 21 hours per machine)
     */
    public function test_machine_day_capping_math(): void
    {
        // We create a partial mock of OeeService to override database queries
        $oeeService = $this->getMockBuilder(OeeService::class)
            ->onlyMethods([
                'aggregateProduction', 
                'aggregateRejects', 
                'aggregateDowntime',
                'getTopDowntimeReasons',
                'getTopRejectReasons',
                'getPlannedCapacities'
            ])
            ->getMock();

        // Setup mock data for 2026-06-01:
        // Machine A: work=8.0, downtime=10.0 (negative runtime!), target=100, actual=50
        // Machine B: work=8.0, downtime=0.0, target=100, actual=120 (over-production!)
        $productionData = [
            '2026-06-01' => [
                'machine_a' => [
                    'work_hours' => 8.0,
                    'target_qty' => 100,
                    'actual_qty' => 50,
                ],
                'machine_b' => [
                    'work_hours' => 8.0,
                    'target_qty' => 100,
                    'actual_qty' => 120,
                ]
            ]
        ];

        $rejectData = [
            '2026-06-01' => [
                'machine_a' => ['reject_qty' => 0],
                'machine_b' => ['reject_qty' => 0],
            ]
        ];

        $downtimeData = [
            '2026-06-01' => [
                'machine_a' => ['downtime_hours' => 10.0],
                'machine_b' => ['downtime_hours' => 0.0],
            ]
        ];

        $oeeService->method('aggregateProduction')->willReturn($productionData);
        $oeeService->method('aggregateRejects')->willReturn($rejectData);
        $oeeService->method('aggregateDowntime')->willReturn($downtimeData);
        $oeeService->method('getTopDowntimeReasons')->willReturn([]);
        $oeeService->method('getTopRejectReasons')->willReturn([]);
        $oeeService->method('getPlannedCapacities')->willReturn(collect([])); // No overrides, fallback to DEFAULT

        // Execute report
        $report = $oeeService->getOeeReport('2026-06-01', '2026-06-01', '404.1', null);

        $this->assertEquals(1, $report['row_count']);
        $row = $report['rows'][0];

        // Raw/uncapped values must still sum up correctly:
        $this->assertEquals(16.0, $row['work_hours']);
        $this->assertEquals(10.0, $row['downtime_hours']);
        $this->assertEquals(170, $row['actual_qty']);
        $this->assertEquals(200, $row['target_qty']);
        $this->assertEquals(0, $row['reject_qty']);

        // Check availability (V2: runtime / planned_capacity):
        // Total Capped Runtime = 8.0 (Machine A: 0.0, Machine B: 8.0)
        // Total Planned Capacity = 42.0 (Machine A: 21.0, Machine B: 21.0)
        // Expected Availability = 8.0 / 42.0 = 0.190476
        $this->assertEquals(8.0 / 42.0, $row['availability']);

        // Check performance: 150 / 200 = 0.75
        $this->assertEquals(0.75, $row['performance']);

        // Quality: 1.0
        $this->assertEquals(1.0, $row['quality']);

        // Expected OEE = (8/42) * 0.75 * 1.0 = 0.142857 (1/7)
        $this->assertEquals((8.0 / 42.0) * 0.75, $row['oee']);

        // Summary totals:
        $summary = $report['summary'];
        $this->assertEquals(8.0 / 42.0, $summary['availability']);
        $this->assertEquals(0.75, $summary['performance']);
        $this->assertEquals(1.0, $summary['quality']);
        $this->assertEquals((8.0 / 42.0) * 0.75, $summary['oee']);
    }

    /**
     * Test Planned Capacity override hierarchy and non-working day exclusions
     */
    public function test_planned_capacity_hierarchy_and_holiday_rules(): void
    {
        $oeeService = $this->getMockBuilder(OeeService::class)
            ->onlyMethods([
                'aggregateProduction', 
                'aggregateRejects', 
                'aggregateDowntime',
                'getTopDowntimeReasons',
                'getTopRejectReasons',
                'getPlannedCapacities'
            ])
            ->getMock();

        // 3 Days setup:
        // Day 1 (2026-06-01): Machine Override on Machine A = 14h. Machine B = Default (21h).
        // Day 2 (2026-06-02): Global Override = 14h. Machine A = 14h, Machine B = 14h.
        // Day 3 (2026-06-03): Holiday (Capacity = 0).
        $productionData = [
            '2026-06-01' => [
                'machine_a' => ['work_hours' => 8.0, 'target_qty' => 100, 'actual_qty' => 80],
                'machine_b' => ['work_hours' => 8.0, 'target_qty' => 100, 'actual_qty' => 90],
            ],
            '2026-06-02' => [
                'machine_a' => ['work_hours' => 8.0, 'target_qty' => 100, 'actual_qty' => 80],
                'machine_b' => ['work_hours' => 8.0, 'target_qty' => 100, 'actual_qty' => 90],
            ],
            '2026-06-03' => [
                'machine_a' => ['work_hours' => 8.0, 'target_qty' => 100, 'actual_qty' => 80],
            ]
        ];

        $oeeService->method('aggregateProduction')->willReturn($productionData);
        $oeeService->method('aggregateRejects')->willReturn([]);
        $oeeService->method('aggregateDowntime')->willReturn([]);
        $oeeService->method('getTopDowntimeReasons')->willReturn([]);
        $oeeService->method('getTopRejectReasons')->willReturn([]);

        // Mock planned capacity collection:
        $capacityData = collect([
            // Day 1: Machine override for machine_a
            (object)[
                'work_date' => '2026-06-01',
                'machine_code' => 'machine_a',
                'shift_1_hours' => 5.0,
                'shift_2_hours' => 5.0,
                'shift_3_hours' => 4.0,
            ],
            // Day 2: Global override
            (object)[
                'work_date' => '2026-06-02',
                'machine_code' => 'GLOBAL',
                'shift_1_hours' => 5.0,
                'shift_2_hours' => 5.0,
                'shift_3_hours' => 4.0,
            ],
            // Day 3: Global holiday (capacity = 0)
            (object)[
                'work_date' => '2026-06-03',
                'machine_code' => 'GLOBAL',
                'shift_1_hours' => 0.0,
                'shift_2_hours' => 0.0,
                'shift_3_hours' => 0.0,
            ]
        ]);

        $oeeService->method('getPlannedCapacities')->willReturn($capacityData);

        // Execute report
        $report = $oeeService->getOeeReport('2026-06-01', '2026-06-03', '404.1', null);

        $this->assertEquals(3, $report['row_count']);
        
        // Assert Day 1 (Machine Override Checkpoint A & Default Checkpoint C)
        $day1 = $report['rows'][0];
        $this->assertEquals('2026-06-01', $day1['date']);
        // Machine A (14h) + Machine B (Default 21h) = 35h
        $this->assertEquals(35.0, $day1['planned_capacity']);
        $this->assertEquals(16.0 / 35.0, $day1['availability']);

        // Assert Day 2 (Global Override Checkpoint B)
        $day2 = $report['rows'][1];
        $this->assertEquals('2026-06-02', $day2['date']);
        // Machine A (Global 14h) + Machine B (Global 14h) = 28h
        $this->assertEquals(28.0, $day2['planned_capacity']);
        $this->assertEquals(16.0 / 28.0, $day2['availability']);

        // Assert Day 3 (Holiday Checkpoint D)
        $day3 = $report['rows'][2];
        $this->assertEquals('2026-06-03', $day3['date']);
        $this->assertEquals(0.0, $day3['planned_capacity']);
        $this->assertNull($day3['availability']);
        $this->assertNull($day3['performance']);
        $this->assertNull($day3['quality']);
        $this->assertNull($day3['oee']);

        // Assert Monthly Summary Exclusion Checkpoint E:
        // Day 3 (Holiday) must NOT contribute to OEE summary totals!
        // Cumulative Work Days: Day 1 & Day 2.
        // Total Planned Capacity = 35.0 (Day 1) + 28.0 (Day 2) = 63.0h
        // Total Capped Runtime = 16.0 (Day 1) + 16.0 (Day 2) = 32.0h
        // Expected Summary Availability = 32.0 / 63.0 = 0.507936
        // Day 3 raw work hours (8.0h) are still in total raw summary work hours (16 + 16 + 8 = 40.0h)
        $summary = $report['summary'];
        $this->assertEquals(40.0, $summary['work_hours']);
        $this->assertEquals(32.0 / 63.0, $summary['availability']);
        $this->assertEquals(63.0, $summary['planned_capacity']);
        $this->assertEquals(32.0, $summary['runtime']);
    }
}
