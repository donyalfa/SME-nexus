<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\IndustrySector;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Sme extends Model
{
    protected $fillable = ['user_id', 'business_name', 'industry_sector'];

    protected function casts(): array
    {
        return ['industry_sector' => IndustrySector::class];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function financialRecords(): HasMany
    {
        return $this->hasMany(FinancialRecord::class);
    }

    public function diagnosticResults(): HasMany
    {
        return $this->hasMany(DiagnosticResult::class);
    }
}
