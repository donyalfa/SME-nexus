<?php

declare(strict_types=1);

namespace App\Services;

use App\DataTransferObjects\FinancialMetrics;
use App\Models\FinancialRecord;
use App\Models\Sme;
use App\Repositories\Contracts\FinancialRecordRepositoryInterface;
use Illuminate\Support\Arr;

/**
 * DETECT: capture raw financial data and turn it into hard metrics.
 * Contains no scoring/judgement – that belongs to UnderstandService.
 */
final class DetectService
{
    /** Fields that carry a money amount in the input payload. */
    public const AMOUNT_FIELDS = [
        'revenue', 'cogs', 'operating_expenses',
        'current_assets', 'current_liabilities',
        'cash_balance', 'receivables', 'payables', 'inventory_value',
    ];

    /** Caps keep ratios finite when a denominator is zero. */
    private const MAX_RATIO         = 10.0;
    private const MAX_RUNWAY_MONTHS = 60.0;
    private const MAX_DAYS          = 365.0;
    private const MAX_TURNOVER      = 100.0;

    public function __construct(
        private readonly FinancialRecordRepositoryInterface $records,
    ) {
    }

    // ------------------------------------------------------------------
    // Record / parse
    // ------------------------------------------------------------------

    /**
     * Persist a validated financial period for an SME.
     *
     * @param array<string, mixed> $data validated input (see StoreFinancialRecordRequest)
     */
    public function record(Sme $sme, array $data): FinancialRecord
    {
        $attributes = Arr::only($data, ['period_date', 'period_type', ...self::AMOUNT_FIELDS]);
        $attributes['sme_id'] = $sme->id;

        return $this->records->create($attributes);
    }

    /**
     * Convert formatted money strings ("Rp 1.500.000,50", "1,500,000.50") into floats.
     * Values that cannot be parsed are left untouched so validation can reject them.
     *
     * @param array<string, mixed> $input
     * @return array<string, mixed>
     */
    public function normalizeInput(array $input): array
    {
        foreach (self::AMOUNT_FIELDS as $field) {
            if (isset($input[$field]) && is_string($input[$field])) {
                $parsed = $this->parseAmount($input[$field]);
                if ($parsed !== null) {
                    $input[$field] = $parsed;
                }
            }
        }

        return $input;
    }

    /**
     * Rules:
     *  - currency symbols / spaces are stripped;
     *  - when both "." and "," appear, the LAST one is the decimal separator;
     *  - a lone separator repeated, or followed by exactly 3 digits ("1.500"), is a
     *    thousands separator, except for a leading zero ("0.500" is a decimal).
     */
    public function parseAmount(string $raw): ?float
    {
        $s = preg_replace('/[^\d.,\-]/u', '', trim($raw));
        if ($s === null || $s === '') {
            return null;
        }

        $negative = str_starts_with($s, '-');
        $s        = ltrim($s, '-');
        if ($s === '' || str_contains($s, '-')) {
            return null;
        }

        $lastDot   = strrpos($s, '.');
        $lastComma = strrpos($s, ',');

        if ($lastDot !== false && $lastComma !== false) {
            $decimal  = $lastDot > $lastComma ? '.' : ',';
            $thousand = $decimal === '.' ? ',' : '.';
            $s        = str_replace($decimal, '#', str_replace($thousand, '', $s));
            $s        = str_replace('#', '.', $s);
        } elseif ($lastDot !== false || $lastComma !== false) {
            $sep           = $lastDot !== false ? '.' : ',';
            $digitsAfter   = strlen($s) - (int) strrpos($s, $sep) - 1;
            $isThousands   = (substr_count($s, $sep) > 1 || $digitsAfter === 3)
                && ! preg_match('/^0[.,]/', $s);
            $s = $isThousands ? str_replace($sep, '', $s) : str_replace($sep, '.', $s);
        }

        if (! is_numeric($s)) {
            return null;
        }

        $value = (float) $s;

        return $negative ? -$value : $value;
    }

    // ------------------------------------------------------------------
    // Metrics
    // ------------------------------------------------------------------

    /**
     * Compute Current Ratio, Net Margin, Cash Runway and Cash Conversion Cycle
     * (plus the supporting figures the UNDERSTAND module needs).
     */
    public function calculateMetrics(FinancialRecord $record): FinancialMetrics
    {
        $days   = $record->period_type->days();
        $months = $record->period_type->months();

        $revenue = $record->revenue;
        $cogs    = $record->cogs;
        $opex    = $record->operating_expenses;

        $grossProfit = $revenue - $cogs;
        $netProfit   = $revenue - $cogs - $opex;

        // Current ratio = current assets / current liabilities
        $currentRatio = $record->current_liabilities > 0.0
            ? min(self::MAX_RATIO, $record->current_assets / $record->current_liabilities)
            : ($record->current_assets > 0.0 ? self::MAX_RATIO : 0.0);

        // Margins (fractions of revenue)
        $netMargin   = $this->marginOf($netProfit, $revenue);
        $grossMargin = $this->marginOf($grossProfit, $revenue);
        $opexRatio   = $revenue > 0.0 ? min(1.0, $opex / $revenue) : ($opex > 0.0 ? 1.0 : 0.0);

        // Cash runway: months the cash balance covers the full monthly cost base (COGS + opex).
        $monthlyCosts = ($cogs + $opex) / $months;
        $runway       = $monthlyCosts > 0.0
            ? min(self::MAX_RUNWAY_MONTHS, $record->cash_balance / $monthlyCosts)
            : ($record->cash_balance > 0.0 ? self::MAX_RUNWAY_MONTHS : 0.0);

        // Cash Conversion Cycle = DSO + DIO - DPO
        $dso = $this->turnoverDays($record->receivables, $revenue, $days);
        $dio = $this->turnoverDays($record->inventory_value, $cogs, $days);
        // Service businesses have no COGS, so supplier credit is measured against opex instead.
        $dpo = $this->turnoverDays($record->payables, $cogs > 0.0 ? $cogs : $opex, $days);
        $ccc = $dso + $dio - $dpo;

        // Annualised inventory turnover; not applicable without stock.
        $turnover = ($record->inventory_value > 0.0 && $cogs > 0.0)
            ? min(self::MAX_TURNOVER, ($cogs * (12 / $months)) / $record->inventory_value)
            : null;

        return new FinancialMetrics(
            currentRatio: round($currentRatio, 2),
            netMargin: round($netMargin, 4),
            grossMargin: $cogs > 0.0 ? round($grossMargin, 4) : null,
            operatingExpenseRatio: round($opexRatio, 4),
            cashRunwayMonths: round($runway, 2),
            daysSalesOutstanding: round($dso, 1),
            daysInventoryOutstanding: round($dio, 1),
            daysPayablesOutstanding: round($dpo, 1),
            cashConversionCycle: round($ccc, 1),
            inventoryTurnover: $turnover === null ? null : round($turnover, 2),
            netProfit: round($netProfit, 2),
        );
    }

    /** profit / revenue clamped to [-1, 1]; zero revenue => -100% if losing money, else 0%. */
    private function marginOf(float $profit, float $revenue): float
    {
        if ($revenue <= 0.0) {
            return $profit < 0.0 ? -1.0 : 0.0;
        }

        return max(-1.0, min(1.0, $profit / $revenue));
    }

    /** balance / flow * periodDays, capped; a balance with no flow is treated as "very slow". */
    private function turnoverDays(float $balance, float $flow, int $periodDays): float
    {
        if ($flow <= 0.0) {
            return $balance > 0.0 ? self::MAX_DAYS : 0.0;
        }

        return min(self::MAX_DAYS, ($balance / $flow) * $periodDays);
    }
}
