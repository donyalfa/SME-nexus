<?php

declare(strict_types=1);

namespace App\Services;

use App\DataTransferObjects\FinancialMetrics;
use App\Enums\HealthStatus;
use App\Enums\IndustrySector;
use App\Models\DiagnosticResult;
use App\Models\FinancialRecord;
use App\Repositories\Contracts\DiagnosticResultRepositoryInterface;

/**
 * UNDERSTAND: turn raw metrics into scores, a health status and plain-language findings.
 * All weights / benchmarks / thresholds come from config/nexus.php.
 */
final class UnderstandService
{
    private const PILLAR_LABELS = [
        'liquidity'     => 'Likuiditas',
        'profitability' => 'Profitabilitas',
        'efficiency'    => 'Efisiensi kas',
        'operational'   => 'Operasional',
    ];

    private const SEVERITY_RANK = ['critical' => 0, 'warning' => 1, 'info' => 2, 'positive' => 3];

    /** kode temuan => [prioritas, tindakan]. Hanya temuan bermasalah yang punya rekomendasi. */
    private const RECOMMENDATIONS = [
        'LIQUIDITY_RISK' => ['high', 'Bebaskan kas jangka pendek: tunda pembelian yang tidak mendesak, negosiasikan tempo pembayaran kewajiban lancar yang lebih panjang, atau siapkan fasilitas modal kerja sebelum kewajiban jatuh tempo.'],
        'PROFITABLE_BUT_CASH_POOR' => ['high', 'Ubah laba menjadi kas: tagih lebih cepat, minta uang muka atau pembayaran bertahap, kejar piutang jatuh tempo setiap minggu, dan kurangi stok berlebih.'],
        'CASH_RUNWAY_LOW' => ['high', 'Susun proyeksi arus kas 13 minggu dan naikkan cadangan kas minimal setara 2-3 bulan biaya (tunda biaya, percepat penagihan, atau ajukan fasilitas kredit).'],
        'LOSS_MAKING' => ['high', 'Cari sumber kerugian: tinjau harga jual terhadap biaya per unit, pangkas atau negosiasikan ulang pos biaya terbesar, dan hentikan produk atau pelanggan yang tidak menguntungkan.'],
        'SLOW_CASH_CYCLE' => ['medium', 'Percepat siklus konversi kas: percepat penagihan, kurangi hari persediaan, dan manfaatkan tempo kredit dari pemasok.'],
        'COLLECTION_DELAY' => ['medium', 'Perketat penagihan: tempo pembayaran lebih pendek, diskon pembayaran dini, pengingat otomatis, dan batas kredit untuk pelanggan yang lambat bayar.'],
        'INVENTORY_DRAG' => ['medium', 'Kurangi hari persediaan: habiskan barang yang lambat laku, pesan dalam jumlah lebih kecil, dan sesuaikan pembelian dengan permintaan nyata.'],
        'EARLY_SUPPLIER_PAYMENT' => ['low', 'Anda membayar pemasok sangat cepat. Negosiasikan tempo yang lebih panjang selama tidak mengorbankan diskon atau hubungan baik.'],
        'HIGH_OPEX' => ['medium', 'Audit biaya operasional per pos; negosiasikan ulang tiga biaya terbesar dan tetapkan target rasio biaya terhadap pendapatan.'],
        'UNBALANCED_PROFILE' => ['low', 'Prioritaskan pilar yang paling lemah: perbaikan di sana paling besar pengaruhnya terhadap skor total.'],
    ];

    public function __construct(
        private readonly DetectService $detect,
        private readonly DiagnosticResultRepositoryInterface $results,
    ) {
    }

    /**
     * Run the full diagnosis for one financial record and persist the result.
     */
    public function diagnose(FinancialRecord $record): DiagnosticResult
    {
        $record->loadMissing('sme');

        $metrics    = $this->detect->calculateMetrics($record);
        $benchmarks = $this->benchmarksFor($record->sme->industry_sector);

        $scores  = $this->scoreMatrix($metrics, $benchmarks);
        $overall = $this->overallScore($scores);
        $status  = HealthStatus::fromScore($overall);

        $findings = $this->buildFindings($metrics, $scores, $benchmarks);

        return $this->results->create([
            'sme_id'               => $record->sme_id,
            'financial_record_id'  => $record->id,
            'liquidity_score'      => $scores['liquidity'],
            'profitability_score'  => $scores['profitability'],
            'efficiency_score'     => $scores['efficiency'],
            'operational_score'    => $scores['operational'],
            'overall_health_score' => $overall,
            'health_status'        => $status,
            'metrics'              => $metrics->toArray(),
            'key_findings'         => $findings,
            'recommendations'      => $this->buildRecommendations($findings, $status),
            'scoring_version'      => (string) config('nexus.scoring_version'),
        ]);
    }

    // ------------------------------------------------------------------
    // Scoring
    // ------------------------------------------------------------------

    /**
     * Normalise raw metrics into the 0-100 pillar matrix.
     *
     * @param array<string, array{worst:int|float, best:int|float}> $benchmarks
     * @return array{liquidity:float, profitability:float, efficiency:float, operational:float}
     */
    public function scoreMatrix(FinancialMetrics $metrics, array $benchmarks): array
    {
        $matrix = [];

        /** @var array<string, array<string, float>> $pillars */
        $pillars = config('nexus.pillars');

        foreach ($pillars as $pillar => $components) {
            $weighted    = 0.0;
            $totalWeight = 0.0;

            foreach ($components as $metricKey => $weight) {
                $value = $metrics->metric($metricKey);
                if ($value === null) {
                    continue; // not applicable (e.g. no inventory): re-weight the remaining metrics
                }

                $weighted    += $this->normalize($value, (float) $benchmarks[$metricKey]['worst'], (float) $benchmarks[$metricKey]['best']) * $weight;
                $totalWeight += $weight;
            }

            $matrix[$pillar] = $totalWeight > 0.0 ? round($weighted / $totalWeight, 2) : 0.0;
        }

        /** @var array{liquidity:float, profitability:float, efficiency:float, operational:float} $matrix */
        return $matrix;
    }

    /**
     * Weighted average using config('nexus.weights'): 30% liquidity, 30% profitability,
     * 20% cash efficiency, 20% operations by default.
     *
     * @param array<string, float> $scores
     */
    public function overallScore(array $scores): float
    {
        /** @var array<string, float> $weights */
        $weights = config('nexus.weights');

        $total = 0.0;
        foreach ($weights as $pillar => $weight) {
            $total += ($scores[$pillar] ?? 0.0) * $weight;
        }

        return round($total, 2);
    }

    /**
     * Linear 0-100 normalisation between "worst" and "best" anchors (works for
     * lower-is-better metrics too because worst > best flips the slope).
     */
    public function normalize(float $value, float $worst, float $best): float
    {
        if ($best === $worst) {
            return 0.0;
        }

        $ratio = ($value - $worst) / ($best - $worst);

        return round(max(0.0, min(1.0, $ratio)) * 100, 2);
    }

    /**
     * Default benchmarks overlaid with the sector's overrides.
     *
     * @return array<string, array{worst:int|float, best:int|float}>
     */
    public function benchmarksFor(IndustrySector $sector): array
    {
        return array_replace_recursive(
            config('nexus.benchmarks.default'),
            config("nexus.benchmarks.industries.{$sector->value}", []),
        );
    }

    // ------------------------------------------------------------------
    // Findings & recommendations
    // ------------------------------------------------------------------

    /**
     * Rule-based, structured findings sorted by severity.
     *
     * @param array<string, float> $scores
     * @param array<string, array{worst:int|float, best:int|float}> $benchmarks
     * @return list<array{code:string, severity:string, title:string, message:string}>
     */
    public function buildFindings(FinancialMetrics $m, array $scores, array $benchmarks): array
    {
        /** @var array<string, int|float> $r */
        $r        = config('nexus.rules');
        $findings = [];
        $cashPoorFlagged = false;

        // --- Likuiditas ------------------------------------------------
        if ($m->currentRatio < $r['critical_current_ratio']) {
            $findings[] = $this->finding('LIQUIDITY_RISK', 'critical', 'Utang jangka pendek tidak tertutupi',
                sprintf('Rasio lancar %s. Aset lancar tidak menutup kewajiban lancar, sehingga pembayaran tepat waktu berisiko.', $this->num($m->currentRatio, 2)));
        } elseif ($m->currentRatio < $r['min_current_ratio']) {
            $findings[] = $this->finding('LIQUIDITY_RISK', 'warning', 'Likuiditas ketat',
                sprintf('Rasio lancar %s, sedikit di bawah 1,0. Hampir tidak ada penyangga jika pelanggan terlambat membayar.', $this->num($m->currentRatio, 2)));
        } elseif ($m->currentRatio >= $r['strong_current_ratio']) {
            $findings[] = $this->finding('STRONG_LIQUIDITY', 'positive', 'Likuiditas longgar',
                sprintf('Rasio lancar %s, penyangga yang kuat untuk kewajiban jangka pendek.', $this->num($m->currentRatio, 2)));
        }

        // --- Margin tinggi tetapi cadangan kas rendah -------------------
        if ($m->netMargin >= $r['high_margin'] && $m->cashRunwayMonths < $r['low_runway_months']) {
            $causes = [];
            if ($m->daysSalesOutstanding > $r['dso_slow_days']) {
                $causes[] = sprintf('pelanggan butuh %s hari untuk membayar', $this->num($m->daysSalesOutstanding, 0));
            }
            if ($m->daysInventoryOutstanding > $r['dio_slow_days']) {
                $causes[] = sprintf('stok mengendap %s hari', $this->num($m->daysInventoryOutstanding, 0));
            }
            $drivers = $causes === [] ? '' : ' Kemungkinan penyebab: ' . implode(' dan ', $causes) . '.';

            $findings[] = $this->finding('PROFITABLE_BUT_CASH_POOR', 'critical', 'Untung di atas kertas, kas tipis',
                sprintf('Margin bersih %s, tetapi kas hanya cukup untuk %s bulan biaya. Laba belum berubah menjadi kas.%s',
                    $this->pct($m->netMargin), $this->num($m->cashRunwayMonths, 1), $drivers));
            $cashPoorFlagged = true;
        }

        // --- Profitabilitas --------------------------------------------
        if ($m->netMargin < 0) {
            $findings[] = $this->finding('LOSS_MAKING', $m->netMargin < -0.10 ? 'critical' : 'warning', 'Bisnis mengalami kerugian',
                sprintf('Margin bersih %s (hasil bersih periode ini Rp %s).', $this->pct($m->netMargin), $this->num($m->netProfit, 0)));
        }

        // --- Cadangan kas (jika belum tercakup di atas) -----------------
        if (! $cashPoorFlagged && $m->cashRunwayMonths < $r['low_runway_months']) {
            $findings[] = $this->finding('CASH_RUNWAY_LOW', $m->cashRunwayMonths < $r['critical_runway_months'] ? 'critical' : 'warning',
                'Cadangan kas rendah',
                sprintf('Kas hanya cukup untuk %s bulan biaya operasional.', $this->num($m->cashRunwayMonths, 1)));
        }

        // --- Siklus konversi kas ---------------------------------------
        $cccWorst = (float) $benchmarks['ccc']['worst'];
        $cccBest  = (float) $benchmarks['ccc']['best'];
        $cccMid   = ($cccWorst + $cccBest) / 2;

        if ($m->cashConversionCycle >= $cccWorst) {
            $findings[] = $this->finding('SLOW_CASH_CYCLE', 'critical', 'Kas tertahan terlalu lama',
                sprintf('Siklus konversi kas %s hari (batas bahaya sektor ini: %s hari).', $this->num($m->cashConversionCycle, 0), $this->num($cccWorst, 0)));
        } elseif ($m->cashConversionCycle > $cccMid) {
            $findings[] = $this->finding('SLOW_CASH_CYCLE', 'warning', 'Siklus kas lebih lambat dari ideal',
                sprintf('Siklus konversi kas %s hari; target sektor ini %s hari atau kurang.', $this->num($m->cashConversionCycle, 0), $this->num($cccBest, 0)));
        } elseif ($m->cashConversionCycle <= $cccBest) {
            $findings[] = $this->finding('FAST_CASH_CYCLE', 'positive', 'Siklus kas efisien',
                sprintf('Kas kembali dalam %s hari, setara atau lebih baik dari target sektor.', $this->num($m->cashConversionCycle, 0)));
        }

        if ($m->daysSalesOutstanding > $r['dso_slow_days']) {
            $findings[] = $this->finding('COLLECTION_DELAY', 'warning', 'Pelanggan membayar lambat',
                sprintf('Piutang baru tertagih rata-rata dalam %s hari.', $this->num($m->daysSalesOutstanding, 0)));
        }

        if ($m->daysInventoryOutstanding > $r['dio_slow_days']) {
            $findings[] = $this->finding('INVENTORY_DRAG', 'warning', 'Perputaran stok lambat',
                sprintf('Stok bertahan sekitar %s hari sebelum terjual.', $this->num($m->daysInventoryOutstanding, 0)));
        }

        if ($m->daysPayablesOutstanding > 0 && $m->daysPayablesOutstanding < $r['short_dpo_days'] && $m->cashConversionCycle > $cccMid) {
            $findings[] = $this->finding('EARLY_SUPPLIER_PAYMENT', 'info', 'Pemasok dibayar sangat cepat',
                sprintf('Utang dagang dilunasi dalam sekitar %s hari, yang memperpanjang siklus kas.', $this->num($m->daysPayablesOutstanding, 0)));
        }

        // --- Operasional -----------------------------------------------
        if ($m->operatingExpenseRatio >= (float) $benchmarks['opex_ratio']['worst']) {
            $findings[] = $this->finding('HIGH_OPEX', 'warning', 'Biaya operasional berat',
                sprintf('Biaya operasional menyerap %s dari pendapatan.', $this->pct($m->operatingExpenseRatio, 0)));
        }

        // --- Keseimbangan profil ---------------------------------------
        if ($scores['profitability'] >= 80) {
            $findings[] = $this->finding('STRONG_PROFITABILITY', 'positive', 'Profitabilitas kuat',
                sprintf('Margin bernilai %s dari 100 untuk sektor ini.', $this->num($scores['profitability'], 0)));
        }

        $gap = max($scores) - min($scores);
        if ($gap >= $r['imbalance_gap']) {
            $strongest = self::PILLAR_LABELS[(string) array_search(max($scores), $scores, true)];
            $weakest   = self::PILLAR_LABELS[(string) array_search(min($scores), $scores, true)];
            $findings[] = $this->finding('UNBALANCED_PROFILE', 'info', 'Profil kesehatan tidak seimbang',
                sprintf('%s jauh di depan %s (selisih %s poin).', $strongest, $weakest, $this->num($gap, 0)));
        }

        usort($findings, fn (array $a, array $b): int => self::SEVERITY_RANK[$a['severity']] <=> self::SEVERITY_RANK[$b['severity']]);

        return $findings;
    }

    /**
     * @param list<array{code:string, severity:string, title:string, message:string}> $findings
     * @return list<array{rank:int, priority:string, code:string, action:string}>
     */
    private function buildRecommendations(array $findings, HealthStatus $status): array
    {
        $recommendations = [];
        $seen            = [];

        if ($status === HealthStatus::CRITICAL) {
            $recommendations[] = [
                'rank'     => 1,
                'priority' => 'high',
                'code'     => 'EMERGENCY_REVIEW',
                'action'   => 'Adakan tinjauan kas minggu ini: daftarkan semua pembayaran 30 hari ke depan terhadap penerimaan yang diharapkan, lalu putuskan apa yang bisa ditunda.',
            ];
        }

        foreach ($findings as $finding) {
            $code = $finding['code'];
            if (isset($seen[$code]) || ! isset(self::RECOMMENDATIONS[$code])) {
                continue;
            }
            $seen[$code] = true;

            [$priority, $action] = self::RECOMMENDATIONS[$code];
            $recommendations[]   = [
                'rank'     => count($recommendations) + 1,
                'priority' => $priority,
                'code'     => $code,
                'action'   => $action,
            ];
        }

        if ($recommendations === []) {
            $recommendations[] = [
                'rank'     => 1,
                'priority' => 'low',
                'code'     => 'MAINTAIN',
                'action'   => 'Tidak ada area bermasalah. Terus catat setiap periode agar tren tetap terlihat.',
            ];
        }

        return $recommendations;
    }

    /** Format angka gaya Indonesia: 1.234,5 */
    private function num(float $value, int $decimals = 1): string
    {
        return number_format($value, $decimals, ',', '.');
    }

    /** Pecahan -> persen, mis. 0.185 => "18,5%". */
    private function pct(float $fraction, int $decimals = 1): string
    {
        return $this->num($fraction * 100, $decimals) . '%';
    }

    /** @return array{code:string, severity:string, title:string, message:string} */
    private function finding(string $code, string $severity, string $title, string $message): array
    {
        return compact('code', 'severity', 'title', 'message');
    }
}
