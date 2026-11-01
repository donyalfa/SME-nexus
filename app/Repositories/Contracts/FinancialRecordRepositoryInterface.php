<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\FinancialRecord;

interface FinancialRecordRepositoryInterface
{
    /** @param array<string, mixed> $attributes */
    public function create(array $attributes): FinancialRecord;
}
