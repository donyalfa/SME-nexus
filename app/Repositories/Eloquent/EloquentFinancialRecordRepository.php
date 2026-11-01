<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent;

use App\Models\FinancialRecord;
use App\Repositories\Contracts\FinancialRecordRepositoryInterface;

final class EloquentFinancialRecordRepository implements FinancialRecordRepositoryInterface
{
    public function create(array $attributes): FinancialRecord
    {
        return FinancialRecord::query()->create($attributes);
    }
}
