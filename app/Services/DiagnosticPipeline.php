<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\DiagnosticResult;
use App\Models\Sme;
use App\Models\User;
use App\Repositories\Contracts\SmeRepositoryInterface;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

/**
 * Orchestrates DETECT -> UNDERSTAND atomically so a failed diagnosis never
 * leaves an orphaned financial record (or SME) behind.
 */
final class DiagnosticPipeline
{
    public function __construct(
        private readonly DetectService $detect,
        private readonly UnderstandService $understand,
        private readonly SmeRepositoryInterface $smes,
    ) {
    }

    /**
     * @param array<string, mixed> $validated output of StoreFinancialRecordRequest::validated()
     */
    public function run(User $user, array $validated): DiagnosticResult
    {
        return DB::transaction(function () use ($user, $validated): DiagnosticResult {
            $sme = ! empty($validated['sme_id'])
                ? ($this->smes->findForUser((int) $validated['sme_id'], $user->id)
                    ?? throw (new ModelNotFoundException())->setModel(Sme::class))
                : $this->smes->create([
                    'user_id'         => $user->id,
                    'business_name'   => $validated['business_name'],
                    'industry_sector' => $validated['industry_sector'],
                ]);

            $record = $this->detect->record($sme, $validated);

            return $this->understand->diagnose($record);
        });
    }
}
