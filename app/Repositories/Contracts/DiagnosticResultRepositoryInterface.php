<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\DiagnosticResult;
use Illuminate\Database\Eloquent\Collection;

interface DiagnosticResultRepositoryInterface
{
    /** @param array<string, mixed> $attributes */
    public function create(array $attributes): DiagnosticResult;

    /** Returns null when missing OR owned by another user. */
    public function findForUser(int $id, int $userId): ?DiagnosticResult;

    /** Latest diagnostic by *reporting period* (not by insertion time). */
    public function latestForSme(int $smeId): ?DiagnosticResult;

    /** The diagnostic for the closest earlier period of the same SME. */
    public function previousFor(DiagnosticResult $result): ?DiagnosticResult;

    /** @return Collection<int, DiagnosticResult> newest period first */
    public function historyForSme(int $smeId, int $limit = 6): Collection;
}
