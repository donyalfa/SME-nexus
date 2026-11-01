<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\HealthStatus;
use App\Models\DiagnosticResult;
use App\Models\User;
use App\Services\DetectService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

final class DiagnosticPipelineTest extends TestCase
{
    use RefreshDatabase;

    /** @return array<string, mixed> */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'business_name'       => 'Toko Maju',
            'industry_sector'     => 'retail',
            'period_type'         => 'monthly',
            'period_date'         => now()->subDay()->toDateString(),
            'revenue'             => 100000,
            'cogs'                => 40000,
            'operating_expenses'  => 40000,
            'current_assets'      => 50000,
            'current_liabilities' => 30000,
            'cash_balance'        => 3000,
            'receivables'         => 40000,
            'payables'            => 20000,
            'inventory_value'     => 5000,
        ], $overrides);
    }

    public function test_profitable_but_cash_poor_business_is_flagged(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('diagnostics.store'), $this->payload())
            ->assertRedirect();

        $result = DiagnosticResult::query()->firstOrFail();
        $codes  = collect($result->key_findings)->pluck('code');

        $this->assertTrue($codes->contains('PROFITABLE_BUT_CASH_POOR'));
        $this->assertSame($result->sme->user_id, $user->id);
        $this->assertContains($result->health_status, HealthStatus::cases());
    }

    public function test_overall_score_uses_the_configured_weights(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->post(route('diagnostics.store'), $this->payload());

        $r        = DiagnosticResult::query()->firstOrFail();
        $expected = round(
            $r->liquidity_score * 0.30 + $r->profitability_score * 0.30
            + $r->efficiency_score * 0.20 + $r->operational_score * 0.20,
            2
        );

        $this->assertEqualsWithDelta($expected, $r->overall_health_score, 0.01);
    }

    public function test_inconsistent_balance_sheet_is_rejected(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('diagnostics.store'), $this->payload(['current_assets' => 1000]))
            ->assertSessionHasErrors('current_assets');

        $this->assertDatabaseCount('financial_records', 0);
    }

    public function test_users_cannot_open_other_users_reports(): void
    {
        $owner = User::factory()->create();
        $this->actingAs($owner)->post(route('diagnostics.store'), $this->payload());
        $id = DiagnosticResult::query()->firstOrFail()->id;

        $this->actingAs(User::factory()->create())
            ->get(route('diagnostics.show', $id))
            ->assertNotFound();

        $this->actingAs($owner)
            ->getJson(route('diagnostics.show', $id))
            ->assertOk()
            ->assertJsonStructure(['data' => ['scores', 'radar' => ['labels', 'datasets'], 'key_findings']]);
    }

    public function test_gross_margin_is_not_applicable_without_cogs(): void
    {
        $this->actingAs(User::factory()->create())
            ->post(route('diagnostics.store'), $this->payload(['cogs' => 0, 'industry_sector' => 'services', 'inventory_value' => 0, 'current_assets' => 50000]))
            ->assertSessionHasNoErrors();

        $this->assertNull(DiagnosticResult::query()->firstOrFail()->metrics['gross_margin']);
    }

    public function test_zero_revenue_is_rejected(): void
    {
        $this->actingAs(User::factory()->create())
            ->post(route('diagnostics.store'), $this->payload(['revenue' => 0]))
            ->assertSessionHasErrors('revenue');
    }

    public function test_duplicate_period_is_rejected(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->post(route('diagnostics.store'), $this->payload());
        $smeId = DiagnosticResult::query()->firstOrFail()->sme_id;

        $this->actingAs($user)
            ->post(route('diagnostics.store'), $this->payload(['sme_id' => $smeId]))
            ->assertSessionHasErrors('period_date');
    }

    #[DataProvider('amounts')]
    public function test_amount_parser(string $raw, ?float $expected): void
    {
        $this->assertSame($expected, app(DetectService::class)->parseAmount($raw));
    }

    /** @return array<string, array{string, ?float}> */
    public static function amounts(): array
    {
        return [
            'indonesian format'   => ['Rp 1.500.000,50', 1500000.5],
            'us format'           => ['1,500,000.50', 1500000.5],
            'thousands only'      => ['2.500.000', 2500000.0],
            'single group'        => ['1.500', 1500.0],
            'plain decimal'       => ['12.5', 12.5],
            'leading zero decimal' => ['0.500', 0.5],
            'garbage'             => ['abc', null],
        ];
    }
}
