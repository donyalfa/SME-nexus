<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\HealthStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DiagnosticResult extends Model
{
    /** Results are immutable snapshots: only created_at is kept. */
    public const UPDATED_AT = null;

    protected $fillable = [
        'sme_id', 'financial_record_id',
        'liquidity_score', 'profitability_score', 'efficiency_score', 'operational_score',
        'overall_health_score', 'health_status',
        'metrics', 'key_findings', 'recommendations', 'scoring_version',
    ];

    protected function casts(): array
    {
        return [
            'liquidity_score'      => 'float',
            'profitability_score'  => 'float',
            'efficiency_score'     => 'float',
            'operational_score'    => 'float',
            'overall_health_score' => 'float',
            'health_status'        => HealthStatus::class,
            'metrics'              => 'array',
            'key_findings'         => 'array',
            'recommendations'      => 'array',
        ];
    }

    public function sme(): BelongsTo
    {
        return $this->belongsTo(Sme::class);
    }

    public function financialRecord(): BelongsTo
    {
        return $this->belongsTo(FinancialRecord::class);
    }

    /**
     * Pillar meta used by the UI (label, weight, score), in radar order.
     *
     * @return array<string, array{label:string, weight:float, score:float}>
     */
    public function pillars(): array
    {
        /** @var array<string, float> $w */
        $w = config('nexus.weights');

        return [
            'liquidity'     => ['label' => 'Likuiditas',      'weight' => $w['liquidity'],     'score' => $this->liquidity_score],
            'profitability' => ['label' => 'Profitabilitas',  'weight' => $w['profitability'], 'score' => $this->profitability_score],
            'efficiency'    => ['label' => 'Efisiensi kas',   'weight' => $w['efficiency'],    'score' => $this->efficiency_score],
            'operational'   => ['label' => 'Operasional',     'weight' => $w['operational'],   'score' => $this->operational_score],
        ];
    }

    /**
     * Chart.js-ready payload for the radar chart, optionally overlaying a previous period.
     *
     * @return array{labels: list<string>, datasets: list<array{label:string, data:list<float>, kind:string}>}
     */
    public function radarPayload(?self $previous = null): array
    {
        $pillars = $this->pillars();

        $datasets = [[
            'label' => $this->periodLabel(),
            'data'  => array_values(array_column($pillars, 'score')),
            'kind'  => 'current',
        ]];

        if ($previous !== null) {
            $datasets[] = [
                'label' => $previous->periodLabel(),
                'data'  => array_values(array_column($previous->pillars(), 'score')),
                'kind'  => 'previous',
            ];
        }

        return [
            'labels'   => array_values(array_column($pillars, 'label')),
            'datasets' => $datasets,
        ];
    }

    public function periodLabel(): string
    {
        return $this->financialRecord?->period_date?->translatedFormat('M Y') ?? 'Saat ini';
    }
}
