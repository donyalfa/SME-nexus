<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PeriodType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class FinancialRecord extends Model
{
    protected $fillable = [
        'sme_id', 'period_date', 'period_type',
        'revenue', 'cogs', 'operating_expenses',
        'current_assets', 'current_liabilities',
        'cash_balance', 'receivables', 'payables', 'inventory_value',
    ];

    protected function casts(): array
    {
        return [
            'period_date'         => 'date',
            'period_type'         => PeriodType::class,
            'revenue'             => 'float',
            'cogs'                => 'float',
            'operating_expenses'  => 'float',
            'current_assets'      => 'float',
            'current_liabilities' => 'float',
            'cash_balance'        => 'float',
            'receivables'         => 'float',
            'payables'            => 'float',
            'inventory_value'     => 'float',
        ];
    }

    public function sme(): BelongsTo
    {
        return $this->belongsTo(Sme::class);
    }

    public function diagnosticResult(): HasOne
    {
        return $this->hasOne(DiagnosticResult::class);
    }
}
