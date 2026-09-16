<?php

namespace Tests\Feature;

use App\Models\MdHeatNumberMirror;
use App\Models\User;
use App\Services\Integration\KanbanKtrResolver;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class KanbanKtrResolverTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Setup Master connection in testing environment (using sqlite in-memory)
        config(['database.connections.master' => config('database.connections.sqlite')]);

        $this->createTestingSchema();
        $this->seedMirrorTestData();
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

        Schema::connection('master')->dropIfExists('md_heat_numbers');
        Schema::connection('master')->create('md_heat_numbers', function (Blueprint $table) {
            $table->id();
            $table->string('traveler_number', 60)->nullable()->unique();
            $table->string('kode_produksi', 50)->nullable();
            $table->date('heat_date')->nullable();
            $table->string('item_code', 50);
            $table->string('item_name', 200)->nullable();
            $table->string('heat_number', 50);
            $table->integer('cor_qty')->default(0);
            $table->string('size', 20)->nullable();
            $table->string('customer', 50)->nullable();
            $table->string('line', 20)->nullable();
            $table->string('status', 20)->default('active');
            $table->timestamps();
            $table->index('heat_number');
            $table->index('item_code');
        });

        Schema::dropIfExists('md_heat_numbers_mirror');
        Schema::create('md_heat_numbers_mirror', function (Blueprint $table) {
            $table->id();
            $table->string('traveler_number', 60)->nullable()->unique();
            $table->string('heat_number', 50);
            $table->string('kode_produksi', 50)->nullable();
            $table->string('item_code', 50);
            $table->string('item_name', 200)->nullable();
            $table->string('size', 20)->nullable();
            $table->string('customer', 50)->nullable();
            $table->string('line', 20)->nullable();
            $table->integer('cor_qty')->default(0);
            $table->string('status', 20)->default('active');
            $table->timestamp('source_updated_at')->nullable();
            $table->timestamp('last_sync_at')->nullable();
            $table->index('heat_number');
            $table->index('item_code');
        });
    }

    protected function seedMirrorTestData(): void
    {
        // 1. KTR-20260915-0001 -> ABB194 (Flange, 77 pcs)
        MdHeatNumberMirror::create([
            'traveler_number' => 'KTR-20260915-0001',
            'heat_number' => 'A214092601',
            'kode_produksi' => 'ABB194',
            'item_code' => '4.101105K.A0032',
            'item_name' => 'SS304 JIS 5K 1-1/4"',
            'size' => '1-1/4"',
            'customer' => 'A06',
            'line' => 'LINE 1',
            'cor_qty' => 77,
            'status' => 'active',
            'source_updated_at' => now(),
            'last_sync_at' => now(),
        ]);

        // 2. KTR-20260915-0002 -> ABB195 (Flange, 52 pcs)
        MdHeatNumberMirror::create([
            'traveler_number' => 'KTR-20260915-0002',
            'heat_number' => 'A214092601',
            'kode_produksi' => 'ABB195',
            'item_code' => '4.101110K.A0032',
            'item_name' => 'SS304 JIS 10K 1-1/4"',
            'size' => '1-1/4"',
            'customer' => 'A06',
            'line' => 'LINE 1',
            'cor_qty' => 52,
            'status' => 'active',
            'source_updated_at' => now(),
            'last_sync_at' => now(),
        ]);

        // 3. KTR-20260915-0003 -> Fitting Out of scope
        MdHeatNumberMirror::create([
            'traveler_number' => 'KTR-20260915-0003',
            'heat_number' => 'A214092601',
            'kode_produksi' => 'FIT001',
            'item_code' => '4.200100K.0001',
            'item_name' => 'SS304 ELBOW 90 DEG 2"',
            'size' => '2"',
            'customer' => 'CUST_FIT',
            'line' => 'LINE 2',
            'cor_qty' => 30,
            'status' => 'active',
            'source_updated_at' => now(),
            'last_sync_at' => now(),
        ]);

        // 4. Historical record without traveler_number (NULL)
        MdHeatNumberMirror::create([
            'traveler_number' => null,
            'heat_number' => 'HISTORICAL-HN-001',
            'kode_produksi' => 'HIST01',
            'item_code' => '4.101105K.A0050',
            'item_name' => 'SS304 JIS 5K 2"',
            'size' => '2"',
            'customer' => 'HIST_CUST',
            'line' => 'LINE 1',
            'cor_qty' => 100,
            'status' => 'active',
            'source_updated_at' => now(),
            'last_sync_at' => now(),
        ]);
    }

    /**
     * Test resolving KTR-20260915-0001 from local mirror without Kanban connection
     */
    public function test_resolves_ktr_0001_correctly(): void
    {
        $resolver = new KanbanKtrResolver;
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
     * Test resolving KTR-20260915-0002 from local mirror without Kanban connection
     */
    public function test_resolves_ktr_0002_correctly(): void
    {
        $resolver = new KanbanKtrResolver;
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
        $resolver = new KanbanKtrResolver;

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
     * Test Multiple KTRs with same Heat and same Item Code resolve distinctly
     */
    public function test_two_distinct_ktrs_for_same_heat_and_item_code_resolve_accurately(): void
    {
        MdHeatNumberMirror::create([
            'traveler_number' => 'KTR-SPLIT-A',
            'heat_number' => 'HEAT-SAME-ITEM',
            'kode_produksi' => 'PROD-SAME',
            'item_code' => '4.101105K.A0032',
            'item_name' => 'SS304 JIS 5K 1-1/4"',
            'size' => '1-1/4"',
            'customer' => 'A06',
            'line' => 'LINE 1',
            'cor_qty' => 40,
            'status' => 'active',
            'source_updated_at' => now(),
            'last_sync_at' => now(),
        ]);

        MdHeatNumberMirror::create([
            'traveler_number' => 'KTR-SPLIT-B',
            'heat_number' => 'HEAT-SAME-ITEM',
            'kode_produksi' => 'PROD-SAME',
            'item_code' => '4.101105K.A0032',
            'item_name' => 'SS304 JIS 5K 1-1/4"',
            'size' => '1-1/4"',
            'customer' => 'A06',
            'line' => 'LINE 1',
            'cor_qty' => 35,
            'status' => 'active',
            'source_updated_at' => now(),
            'last_sync_at' => now(),
        ]);

        $resolver = new KanbanKtrResolver;

        $resA = $resolver->resolve('KTR-SPLIT-A');
        $this->assertTrue($resA['success']);
        $this->assertEquals(40, $resA['data']['qty_cor']);
        $this->assertEquals('KTR-SPLIT-A', $resA['data']['ktr']);

        $resB = $resolver->resolve('KTR-SPLIT-B');
        $this->assertTrue($resB['success']);
        $this->assertEquals(35, $resB['data']['qty_cor']);
        $this->assertEquals('KTR-SPLIT-B', $resB['data']['ktr']);
    }

    /**
     * Test Pilot Flange Scope: Fitting items are rejected
     */
    public function test_rejects_out_of_scope_non_flange_kitir(): void
    {
        $resolver = new KanbanKtrResolver;
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
        $resolver = new KanbanKtrResolver;
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
     * Test PullMasterHeatNumbers command pulls traveler_number from Master Data
     */
    public function test_pull_command_pulls_traveler_number_from_master(): void
    {
        DB::connection('master')->table('md_heat_numbers')->insert([
            'traveler_number' => 'KTR-PULL-999',
            'heat_number' => 'HN-PULL-999',
            'kode_produksi' => 'KD-999',
            'item_code' => '4.101105K.A0099',
            'item_name' => 'SS304 JIS 5K 3"',
            'size' => '3"',
            'customer' => 'CUST_99',
            'line' => 'LINE 2',
            'cor_qty' => 65,
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->artisan('pull:master-heat-numbers')
            ->expectsOutputToContain('Heat Number synchronization finished.')
            ->assertExitCode(0);

        $pulled = MdHeatNumberMirror::where('traveler_number', 'KTR-PULL-999')->first();
        $this->assertNotNull($pulled);
        $this->assertEquals('HN-PULL-999', $pulled->heat_number);
        $this->assertEquals('KD-999', $pulled->kode_produksi);
        $this->assertEquals(65, $pulled->cor_qty);
    }

    /**
     * Test manual search heat numbers endpoint still works for historical rows without KTR
     */
    public function test_manual_search_heat_numbers_works_for_historical_rows(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->getJson('/api/search/heat-numbers?q=HISTORICAL');
        $response->assertStatus(200);
        $json = $response->json();

        $this->assertNotEmpty($json);
        $this->assertEquals('HISTORICAL-HN-001', $json[0]['heat_number']);
        $this->assertEquals('SS304 JIS 5K 2"', $json[0]['item_name']);
    }

    /**
     * Test Pull command does not collapse multiple historical NULL traveler rows
     */
    public function test_pull_command_does_not_collapse_multiple_historical_null_rows(): void
    {
        DB::connection('master')->table('md_heat_numbers')->insert([
            [
                'traveler_number' => null,
                'heat_number' => 'HIST-001',
                'kode_produksi' => 'KD-HIST1',
                'item_code' => '4.101105K.A0001',
                'item_name' => 'SS304 JIS 5K 1/2"',
                'size' => '1/2"',
                'customer' => 'CUST_A',
                'line' => 'LINE 1',
                'cor_qty' => 50,
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'traveler_number' => null,
                'heat_number' => 'HIST-002',
                'kode_produksi' => 'KD-HIST2',
                'item_code' => '4.101105K.A0002',
                'item_name' => 'SS304 JIS 5K 3/4"',
                'size' => '3/4"',
                'customer' => 'CUST_B',
                'line' => 'LINE 2',
                'cor_qty' => 60,
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $this->artisan('pull:master-heat-numbers')
            ->assertExitCode(0);

        $nullRows = MdHeatNumberMirror::whereNull('traveler_number')->get();
        // Includes the seeded historical row (HISTORICAL-HN-001) plus the two new ones
        $this->assertGreaterThanOrEqual(3, $nullRows->count());
        $this->assertTrue($nullRows->contains('heat_number', 'HIST-001'));
        $this->assertTrue($nullRows->contains('heat_number', 'HIST-002'));
    }

    /**
     * Test duplicate traveler_number is prevented by database unique constraint
     */
    public function test_duplicate_traveler_number_is_prevented_by_unique_constraint(): void
    {
        $this->expectException(\Illuminate\Database\QueryException::class);

        MdHeatNumberMirror::create([
            'traveler_number' => 'KTR-20260915-0001', // Already seeded in setUp
            'heat_number' => 'A214092601',
            'kode_produksi' => 'DUP001',
            'item_code' => '4.101105K.A0032',
            'item_name' => 'SS304 JIS 5K 1-1/4"',
            'size' => '1-1/4"',
            'customer' => 'A06',
            'line' => 'LINE 1',
            'cor_qty' => 10,
            'status' => 'active',
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

    /**
     * Test KTR normalization with lowercase and leading/trailing whitespace
     */
    public function test_resolver_handles_whitespace_and_lowercase_normalization(): void
    {
        $resolver = new KanbanKtrResolver;
        $result = $resolver->resolve("  ktr-20260915-0001\n\t ");

        $this->assertTrue($result['success']);
        $this->assertEquals('KTR-20260915-0001', $result['data']['ktr']);
        $this->assertEquals('ABB194', $result['data']['kode_produksi']);
    }
}
