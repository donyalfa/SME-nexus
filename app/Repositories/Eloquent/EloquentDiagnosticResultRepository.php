<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent;

use App\Models\DiagnosticResult;
use App\Repositories\Contracts\DiagnosticResultRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

final class EloquentDiagnosticResultRepository implements DiagnosticResultRepositoryInterface
{
    public function create(array $attributes): DiagnosticResult
    {
        return DiagnosticResult::query()->create($attributes)->load(['sme', 'financialRecord']);
    }

    public function findForUser(int $id, int $userId): ?DiagnosticResult
    {
        return DiagnosticResult::query()
            ->with(['sme', 'financialRecord'])
            ->whereKey($id)
            ->whereHas('sme', fn (Builder $q) => $q->where('user_id', $userId))
            ->first();
    }

    public function latestForSme(int $smeId): ?DiagnosticResult
    {
        return $this->chronological($smeId)->first();
    }

    public function previousFor(DiagnosticResult $result): ?DiagnosticResult
    {
        $result->loadMissing('financialRecord');

        return $this->chronological($result->sme_id)
            ->where('financial_records.period_date', '<', $result->financialRecord->period_date->toDateString())
            ->first();
    }

    public function historyForSme(int $smeId, int $limit = 6): Collection
    {
        return $this->chronological($smeId)->limit($limit)->get();
    }

    /** Diagnostics of one SME ordered by reporting period, newest first. */
    private function chronological(int $smeId): Builder
    {
        return DiagnosticResult::query()
            ->select('diagnostic_results.*')
            ->join('financial_records', 'financial_records.id', '=', 'diagnostic_results.financial_record_id')
            ->where('diagnostic_results.sme_id', $smeId)
            ->with('financialRecord')
            ->orderByDesc('financial_records.period_date')
            ->orderByDesc('diagnostic_results.id');
    }
}
