<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\IndustrySector;
use App\Enums\PeriodType;
use App\Http\Requests\StoreFinancialRecordRequest;
use App\Repositories\Contracts\DiagnosticResultRepositoryInterface;
use App\Repositories\Contracts\SmeRepositoryInterface;
use App\Services\DiagnosticPipeline;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Thin HTTP layer: all business logic lives in the services. */
final class SmeDiagnosticController extends Controller
{
    public function __construct(
        private readonly SmeRepositoryInterface $smes,
        private readonly DiagnosticResultRepositoryInterface $results,
        private readonly DiagnosticPipeline $pipeline,
    ) {
    }

    /** Dashboard: latest diagnostic (radar + indicators) and recent history. */
    public function index(Request $request): View
    {
        $userId = (int) $request->user()->id;
        $smes   = $this->smes->listForUser($userId);

        $sme = $request->filled('sme')
            ? $smes->firstWhere('id', (int) $request->query('sme'))
            : $smes->first();

        abort_if($request->filled('sme') && $sme === null, 404);

        $latest   = $sme ? $this->results->latestForSme($sme->id) : null;
        $previous = $latest ? $this->results->previousFor($latest) : null;

        return view('diagnostics.index', [
            'smes'     => $smes,
            'sme'      => $sme,
            'latest'   => $latest,
            'previous' => $previous,
            'radar'    => $latest?->radarPayload($previous),
            'history'  => $sme ? $this->results->historyForSme($sme->id) : collect(),
        ]);
    }

    /** Detect input form. */
    public function create(Request $request): View
    {
        return view('diagnostics.create', [
            'smes'        => $this->smes->listForUser((int) $request->user()->id),
            'sectors'     => IndustrySector::cases(),
            'periodTypes' => PeriodType::cases(),
        ]);
    }

    /** Detect (record the data) and immediately Understand (diagnose it). */
    public function store(StoreFinancialRecordRequest $request): RedirectResponse
    {
        $result = $this->pipeline->run($request->user(), $request->validated());

        return redirect()
            ->route('diagnostics.show', $result->id)
            ->with('status', 'Diagnosis selesai.');
    }

    /** Full report. Add ?format=json (or Accept: application/json) for the radar JSON. */
    public function show(Request $request, int $id): View|JsonResponse
    {
        $result = $this->results->findForUser($id, (int) $request->user()->id);
        abort_if($result === null, 404);

        $previous = $this->results->previousFor($result);
        $radar    = $result->radarPayload($previous);

        if ($request->wantsJson() || $request->query('format') === 'json') {
            return response()->json(['data' => [
                'id'                   => $result->id,
                'business'             => $result->sme->business_name,
                'industry_sector'      => $result->sme->industry_sector->value,
                'period_date'          => $result->financialRecord->period_date->toDateString(),
                'period_type'          => $result->financialRecord->period_type->value,
                'overall_health_score' => $result->overall_health_score,
                'health_status'        => $result->health_status->value,
                'scores'               => array_map(fn (array $p): float => $p['score'], $result->pillars()),
                'radar'                => $radar,
                'metrics'              => $result->metrics,
                'key_findings'         => $result->key_findings,
                'recommendations'      => $result->recommendations,
                'scoring_version'      => $result->scoring_version,
            ]]);
        }

        return view('diagnostics.show', [
            'result'   => $result,
            'previous' => $previous,
            'radar'    => $radar,
        ]);
    }
}
