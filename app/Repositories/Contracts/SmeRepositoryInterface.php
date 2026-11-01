<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\Sme;
use Illuminate\Database\Eloquent\Collection;

interface SmeRepositoryInterface
{
    /** @return Collection<int, Sme> */
    public function listForUser(int $userId): Collection;

    /** Returns null when the SME does not exist OR belongs to someone else. */
    public function findForUser(int $smeId, int $userId): ?Sme;

    /** @param array<string, mixed> $attributes */
    public function create(array $attributes): Sme;
}
