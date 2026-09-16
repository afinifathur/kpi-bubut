<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\Integration\KanbanKtrResolver;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class KanbanKtrResolverTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Setup Master & Kanban tables in testing environment (using sqlite in-memory)
        config(['database.connections.master' => config('database.connections.sqlite')]);
        config(['database.connections.kanban' => config('database.connections.sqlite')]);

        $this->createTestingSchema();
        $this->seedKanbanTestData();
    }

    protected function createTestingSchema(): void
    {
        Schema::connection('master')->dropIfExists('users');
        Schema::connection('master')->create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->string('role')->default('operator');
            $table->string('scope')->nullable();
            $table->string('department_code')->nullable();
            $table->string('tim')->nullable();
            $table->text('allowed_apps')->nullable();
            $table->text('additional_department_codes')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::dropIfExists('md_machines_mirror');
        Schema::create('md_machines_mirror', function (Blueprint $table) {
            $table->id();
            $table->string('code');
            $table->string('name');
            $table->string('line_code')->nullable();
            $table->string('status')->default('active');
            $table->timestamps();
        });
        Schema::connection('kanban')->dropIfExists('sand_casting_casting_result_lines');
        Schema::connection('kanban')->dropIfExists('sand_casting_casting_results');
        Schema::connection('kanban')->dropIfExists('sand_casting_casting_order_lines');
        Schema::connection('kanban')->dropIfExists('production_plans');

        Schema::connection('kanban')->create('production_plans', function (Blueprint $table) {
            $table->id();
            $table->string('code')->nullable();
            $table->string('item_code');
            $table->string('item_name');
            $table->string('size')->nullable();
            $table->string('customer')->nullable();
            $table->integer('line_number')->nullable();
            $table->string('product_scope')->nullable();
            $table->timestamps();
        });

        Schema::connection('kanban')->create('sand_casting_casting_results', function (Blueprint $table) {
            $table->id();
            $table->string('heat_number', 50);
            $table->date('cast_date')->nullable();
            $table->timestamps();
        });

        Schema::connection('kanban')->create('sand_casting_casting_order_lines', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('production_plan_id')->nullable();
            $table->string('code')->nullable();
            $table->string('item_name')->nullable();
            $table->string('size')->nullable();
            $table->string('customer')->nullable();
            $table->timestamps();
        });

        Schema::connection('kanban')->create('sand_casting_casting_result_lines', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('sand_casting_casting_result_id');
            $table->unsignedBigInteger('sand_casting_casting_order_line_id')->nullable();
            $table->unsignedBigInteger('production_plan_id')->nullable();
            $table->string('traveler_number', 60)->unique();
            $table->integer('qty_good');
            $table->timestamps();
        });
    }

    protected function seedKanbanTestData(): void
    {
        // 1. Production Plan 1: ABB194 (Flange)
        $plan1Id = DB::connection('kanban')->table('production_plans')->insertGetId([
            'code' => 'ABB194',
            'item_code' => '4.101105K.A0032',
            'item_name' => 'SS304 JIS 5K 1-1/4"',
            'size' => '1-1/4"',
            'customer' => 'A06',
            'line_number' => 1,
            'product_scope' => 'FLANGE_STAINLESS',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 2. Production Plan 2: ABB195 (Flange)
        $plan2Id = DB::connection('kanban')->table('production_plans')->insertGetId([
            'code' => 'ABB195',
            'item_code' => '4.101110K.A0032',
            'item_name' => 'SS304 JIS 10K 1-1/4"',
            'size' => '1-1/4"',
            'customer' => 'A06',
            'line_number' => 1,
            'product_scope' => 'FLANGE_STAINLESS',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 3. Production Plan 3: Fitting (Out of scope)
        $planFittingId = DB::connection('kanban')->table('production_plans')->insertGetId([
            'code' => 'FIT001',
            'item_code' => '4.200100K.0001',
            'item_name' => 'SS304 ELBOW 90 DEG 2"',
            'size' => '2"',
            'customer' => 'CUST_FIT',
            'line_number' => 2,
            'product_scope' => 'FITTING_STAINLESS',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Shared Casting Result (Heat Number: A214092601)
        $resultId = DB::connection('kanban')->table('sand_casting_casting_results')->insertGetId([
            'heat_number' => 'A214092601',
            'cast_date' => '2026-09-15',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Result Line 1: KTR-20260915-0001 -> ABB194 (77 pcs)
        DB::connection('kanban')->table('sand_casting_casting_result_lines')->insert([
            'sand_casting_casting_result_id' => $resultId,
            'production_plan_id' => $plan1Id,
            'traveler_number' => 'KTR-20260915-0001',
            'qty_good' => 77,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Result Line 2: KTR-20260915-0002 -> ABB195 (52 pcs)
        DB::connection('kanban')->table('sand_casting_casting_result_lines')->insert([
            'sand_casting_casting_result_id' => $resultId,
            'production_plan_id' => $plan2Id,
            'traveler_number' => 'KTR-20260915-0002',
            'qty_good' => 52,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Result Line 3: KTR-20260915-0003 -> Fitting Out of scope
        DB::connection('kanban')->table('sand_casting_casting_result_lines')->insert([
            'sand_casting_casting_result_id' => $resultId,
            'production_plan_id' => $planFittingId,
            'traveler_number' => 'KTR-20260915-0003',
            'qty_good' => 30,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Test resolving KTR-20260915-0001
     */
    public function test_resolves_ktr_0001_correctly(): void
    {
        $resolver = new KanbanKtrResolver('kanban');
        $result = $resolver->resolve('KTR-20260915-0001');

        $this->assertTrue($result['success']);
        $data = $result['data'];

        $this->assertEquals('KTR-20260915-0001', $data['ktr']);
        $this->assertEquals('A214092601', $data['heat_number']);
        $this->assertEquals('ABB194', $data['kode_produksi']);
        $this->assertEquals('4.101105K.A0032', $data['item_code']);
        $this->assertEquals('SS304 JIS 5K 1-1/4"', $data['item_name']);
        $this->assertEquals('1-1/4"', $data['size']);
        $this->assertEquals('A06', $data['customer']);
        $this->assertEquals('LINE 1', $data['line']);
        $this->assertEquals(77, $data['qty_cor']);
    }

    /**
     * Test resolving KTR-20260915-0002
     */
    public function test_resolves_ktr_0002_correctly(): void
    {
        $resolver = new KanbanKtrResolver('kanban');
        $result = $resolver->resolve('KTR-20260915-0002');

        $this->assertTrue($result['success']);
        $data = $result['data'];

        $this->assertEquals('KTR-20260915-0002', $data['ktr']);
        $this->assertEquals('A214092601', $data['heat_number']);
        $this->assertEquals('ABB195', $data['kode_produksi']);
        $this->assertEquals('4.101110K.A0032', $data['item_code']);
        $this->assertEquals('SS304 JIS 10K 1-1/4"', $data['item_name']);
        $this->assertEquals('1-1/4"', $data['size']);
        $this->assertEquals('A06', $data['customer']);
        $this->assertEquals('LINE 1', $data['line']);
        $this->assertEquals(52, $data['qty_cor']);
    }

    /**
     * Test Cross-Item Safety:
     * KTR-20260915-0001 must not resolve to ABB195
     * KTR-20260915-0002 must not resolve to ABB194
     */
    public function test_cross_item_safety_prevents_swapping(): void
    {
        $resolver = new KanbanKtrResolver('kanban');

        $res1 = $resolver->resolve('KTR-20260915-0001');
        $this->assertNotEquals('ABB195', $res1['data']['kode_produksi']);
        $this->assertNotEquals('4.101110K.A0032', $res1['data']['item_code']);
        $this->assertEquals('ABB194', $res1['data']['kode_produksi']);

        $res2 = $resolver->resolve('KTR-20260915-0002');
        $this->assertNotEquals('ABB194', $res2['data']['kode_produksi']);
        $this->assertNotEquals('4.101105K.A0032', $res2['data']['item_code']);
        $this->assertEquals('ABB195', $res2['data']['kode_produksi']);
    }

    /**
     * Test Pilot Flange Scope: Fitting items are rejected
     */
    public function test_rejects_out_of_scope_non_flange_kitir(): void
    {
        $resolver = new KanbanKtrResolver('kanban');
        $res = $resolver->resolve('KTR-20260915-0003');

        $this->assertFalse($res['success']);
        $this->assertEquals('OUT_OF_SCOPE', $res['error_code']);
        $this->assertStringContainsString('di luar scope KPI-Bubut Flange', $res['message']);
    }

    /**
     * Test API endpoint /api/resolve/ktr
     */
    public function test_resolve_ktr_api_endpoint(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->getJson('/api/resolve/ktr?code=KTR-20260915-0001');
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'ktr' => 'KTR-20260915-0001',
                    'heat_number' => 'A214092601',
                    'kode_produksi' => 'ABB194',
                    'item_code' => '4.101105K.A0032',
                    'qty_cor' => 77,
                ],
            ]);
    }

    /**
     * Test that qty_cor is returned as Casting info and distinct from bubut output
     */
    public function test_qty_cor_is_distinct_and_not_bubut_output(): void
    {
        $resolver = new KanbanKtrResolver('kanban');
        $res = $resolver->resolve('KTR-20260915-0001');

        $this->assertTrue($res['success']);
        // Verify key is explicitly qty_cor
        $this->assertArrayHasKey('qty_cor', $res['data']);
        $this->assertArrayNotHasKey('actual_qty', $res['data']);
        $this->assertEquals(77, $res['data']['qty_cor']);
    }

    /**
     * Test API endpoint with non-existent KTR returns 404
     */
    public function test_resolve_ktr_api_not_found(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->getJson('/api/resolve/ktr?code=KTR-99999999-9999');
        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'error_code' => 'NOT_FOUND',
            ]);
    }

    /**
     * Test API endpoint with out of scope KTR returns 422
     */
    public function test_resolve_ktr_api_out_of_scope(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->getJson('/api/resolve/ktr?code=KTR-20260915-0003');
        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'error_code' => 'OUT_OF_SCOPE',
            ]);
    }

    /**
     * Test production create page renders without any PHP/Blade errors
     */
    public function test_production_create_page_renders_successfully(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/production/create');
        $response->assertStatus(200);
        $response->assertSee('Input Hasil Produksi');
        $response->assertSee('Scan Kitir');
    }
}
