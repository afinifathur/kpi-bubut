<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Http\Controllers\PlannedCapacityController;
use ReflectionMethod;

class PlannedCapacitySummaryTest extends TestCase
{
    /**
     * Test PlannedCapacityController::calculateSummary method with mock data.
     */
    public function test_calculate_summary(): void
    {
        $controller = new PlannedCapacityController();
        $method = new ReflectionMethod(PlannedCapacityController::class, 'calculateSummary');
        $method->setAccessible(true);

        // Mock gridData matching the scenario
        // Day 1: Normal (21 jam, 0 overtime)
        // Day 2: 8, 0, 8 (16 jam, 2 overtime)
        // Day 3: 0, 0, 0 (0 jam, 0 overtime)
        $gridData = [
            [
                'date' => '2026-06-01',
                'shift_1' => 7.0,
                'shift_2' => 7.0,
                'shift_3' => 7.0,
                'total' => 21.0,
                'notes' => '',
                'status' => 'Default'
            ],
            [
                'date' => '2026-06-02',
                'shift_1' => 8.0,
                'shift_2' => 0.0,
                'shift_3' => 8.0,
                'total' => 16.0,
                'notes' => 'Overtime shift 1 and 3',
                'status' => 'Exception'
            ],
            [
                'date' => '2026-06-03',
                'shift_1' => 0.0,
                'shift_2' => 0.0,
                'shift_3' => 0.0,
                'total' => 0.0,
                'notes' => 'Holiday',
                'status' => 'Exception'
            ],
        ];

        // We also mock the machines collection
        $machines = collect([
            (object)['code' => 'r-bc.84', 'name' => 'BUBUT CNC FLANGE 84']
        ]);

        // Test GLOBAL Scope
        $summaryGlobal = $method->invoke($controller, $gridData, 'GLOBAL', $machines);

        $this->assertEquals(37.0, $summaryGlobal['total_capacity']);
        $this->assertEquals(2, $summaryGlobal['production_days']);
        $this->assertEquals(1, $summaryGlobal['holiday_days']);
        $this->assertEquals(18.5, $summaryGlobal['avg_capacity']);
        $this->assertEquals(2.0, $summaryGlobal['total_overtime']);
        $this->assertEquals('GLOBAL', $summaryGlobal['scope_line1']);
        $this->assertEquals('Semua Mesin', $summaryGlobal['scope_line2']);

        // Test Machine Scope with existing code
        $summaryMachine = $method->invoke($controller, $gridData, 'r-bc.84', $machines);
        $this->assertEquals('R-BC.84', $summaryMachine['scope_line1']);
        $this->assertEquals('BUBUT CNC FLANGE 84', $summaryMachine['scope_line2']);

        // Test Machine Scope with fallback code (not in collection)
        $summaryFallback = $method->invoke($controller, $gridData, 'r-bc.81', $machines);
        $this->assertEquals('R-BC.81', $summaryFallback['scope_line1']);
        $this->assertEquals('R-BC.81', $summaryFallback['scope_line2']);

        // Test average capacity fallback when production days is 0
        $gridDataZero = [
            [
                'date' => '2026-06-01',
                'shift_1' => 0.0,
                'shift_2' => 0.0,
                'shift_3' => 0.0,
                'total' => 0.0,
                'notes' => 'Holiday',
                'status' => 'Exception'
            ],
        ];
        $summaryZero = $method->invoke($controller, $gridDataZero, 'GLOBAL', $machines);
        $this->assertEquals(0.0, $summaryZero['total_capacity']);
        $this->assertEquals(0, $summaryZero['production_days']);
        $this->assertEquals(1, $summaryZero['holiday_days']);
        $this->assertEquals(0.0, $summaryZero['avg_capacity']);
    }
}
