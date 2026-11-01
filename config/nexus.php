<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| SME NEXUS – Detect & Understand configuration
|--------------------------------------------------------------------------
| Every number that influences a score lives here so it can be tuned
| (or overridden per industry) without touching service code.
| NOTE: the benchmark values are illustrative defaults – calibrate them with
| real sector data before using the scores for lending / investment decisions.
*/

return [

    // Selama fitur dikembangkan: lewati login dan pakai satu demo user (lihat UseDemoUser).
    // Set NEXUS_DEMO_MODE=false di production.
    'demo_mode' => env('NEXUS_DEMO_MODE', true),

    // Bahasa antarmuka (id/en). Diterapkan di AppServiceProvider::boot().
    'locale' => env('NEXUS_LOCALE', 'id'),

    // Stored on each diagnostic so old reports stay explainable after a formula change.
    'scoring_version' => '1.0',

    // Overall health = weighted average of the four pillar scores (must sum to 1.0).
    'weights' => [
        'liquidity'     => 0.30,
        'profitability' => 0.30,
        'efficiency'    => 0.20,
        'operational'   => 0.20,
    ],

    // Minimum overall score for each status; anything below "warning" is CRITICAL.
    'status_thresholds' => [
        'excellent' => 80,
        'stable'    => 60,
        'warning'   => 40,
    ],

    // Which metrics feed which pillar, and how much each metric counts inside it.
    'pillars' => [
        'liquidity'     => ['current_ratio' => 0.6, 'cash_runway_months' => 0.4],
        'profitability' => ['net_margin' => 0.7, 'gross_margin' => 0.3],
        'efficiency'    => ['ccc' => 1.0],
        'operational'   => ['opex_ratio' => 0.6, 'inventory_turnover' => 0.4],
    ],

    /*
    | Normalisation anchors: a value at/below "worst" scores 0, at/above "best"
    | scores 100, linear in between. For "lower is better" metrics (ccc, opex_ratio)
    | worst > best, which the same formula handles.
    */
    'benchmarks' => [
        'default' => [
            'current_ratio'      => ['worst' => 0.5,   'best' => 2.0],
            'cash_runway_months' => ['worst' => 0.5,   'best' => 4.0],
            'net_margin'         => ['worst' => -0.10, 'best' => 0.15],
            'gross_margin'       => ['worst' => 0.15,  'best' => 0.50],
            'ccc'                => ['worst' => 120,   'best' => 20],
            'opex_ratio'         => ['worst' => 0.60,  'best' => 0.15],
            'inventory_turnover' => ['worst' => 1.0,   'best' => 8.0],
        ],
        // Only list what differs from "default".
        'industries' => [
            'retail' => [
                'gross_margin'       => ['worst' => 0.15, 'best' => 0.40],
                'ccc'                => ['worst' => 60,   'best' => 15],
                'inventory_turnover' => ['worst' => 2.0,  'best' => 12.0],
            ],
            'manufacturing' => [
                'net_margin' => ['worst' => -0.05, 'best' => 0.12],
                'ccc'        => ['worst' => 150,   'best' => 50],
            ],
            'food_beverage' => [
                'gross_margin'       => ['worst' => 0.30, 'best' => 0.65],
                'ccc'                => ['worst' => 40,   'best' => 5],
                'inventory_turnover' => ['worst' => 6.0,  'best' => 24.0],
            ],
            'services' => [
                'gross_margin' => ['worst' => 0.30, 'best' => 0.75],
                'ccc'          => ['worst' => 90,   'best' => 15],
                'opex_ratio'   => ['worst' => 0.70, 'best' => 0.25],
            ],
            'agriculture' => [
                'ccc'        => ['worst' => 180, 'best' => 60],
                'net_margin' => ['worst' => -0.10, 'best' => 0.12],
            ],
            'technology' => [
                'gross_margin' => ['worst' => 0.40, 'best' => 0.80],
                'net_margin'   => ['worst' => -0.10, 'best' => 0.20],
                'opex_ratio'   => ['worst' => 0.80, 'best' => 0.30],
            ],
        ],
    ],

    // Thresholds used by the finding rules in UnderstandService.
    'rules' => [
        'high_margin'            => 0.10, // net margin considered "high"
        'low_runway_months'      => 2.0,
        'critical_runway_months' => 1.0,
        'critical_current_ratio' => 0.8,
        'min_current_ratio'      => 1.0,
        'strong_current_ratio'   => 1.5,
        'dso_slow_days'          => 60,
        'dio_slow_days'          => 90,
        'short_dpo_days'         => 15,
        'imbalance_gap'          => 40,   // strongest vs weakest pillar (points)
    ],
];
