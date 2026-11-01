<?php

declare(strict_types=1);

namespace App\DataTransferObjects;

/**
 * Immutable result of DetectService::calculateMetrics().
 * Ratios/margins are fractions (0.15 = 15%); *_days are in days; runway in months.
 */
final readonly class FinancialMetrics
{
    public function __construct(
        public float $currentRatio,
        public float $netMargin,
        public ?float $grossMargin, // null = tidak berlaku (tanpa HPP)
        public float $operatingExpenseRatio,
        public float $cashRunwayMonths,
        public float $daysSalesOutstanding,
        public float $daysInventoryOutstanding,
        public float $daysPayablesOutstanding,
        public float $cashConversionCycle,
        public ?float $inventoryTurnover, // null = not applicable (no inventory / no COGS)
        public float $netProfit,
    ) {
    }

    /**
     * Value for a benchmark key used in config('nexus.pillars').
     * Returns null when the metric is not applicable to this business.
     */
    public function metric(string $benchmarkKey): ?float
    {
        return match ($benchmarkKey) {
            'current_ratio'      => $this->currentRatio,
            'cash_runway_months' => $this->cashRunwayMonths,
            'net_margin'         => $this->netMargin,
            'gross_margin'       => $this->grossMargin,
            'ccc'                => $this->cashConversionCycle,
            'opex_ratio'         => $this->operatingExpenseRatio,
            'inventory_turnover' => $this->inventoryTurnover,
            default              => throw new \InvalidArgumentException("Unknown metric key [{$benchmarkKey}]."),
        };
    }

    /** @return array<string, float|null> snapshot persisted in diagnostic_results.metrics */
    public function toArray(): array
    {
        return [
            'current_ratio'           => $this->currentRatio,
            'net_margin'              => $this->netMargin,
            'gross_margin'            => $this->grossMargin,
            'operating_expense_ratio' => $this->operatingExpenseRatio,
            'cash_runway_months'      => $this->cashRunwayMonths,
            'dso'                     => $this->daysSalesOutstanding,
            'dio'                     => $this->daysInventoryOutstanding,
            'dpo'                     => $this->daysPayablesOutstanding,
            'cash_conversion_cycle'   => $this->cashConversionCycle,
            'inventory_turnover'      => $this->inventoryTurnover,
            'net_profit'              => $this->netProfit,
        ];
    }
}
