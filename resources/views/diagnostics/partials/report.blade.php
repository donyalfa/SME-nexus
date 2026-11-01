{{-- Expects: $result (DiagnosticResult), $previous (?DiagnosticResult), $radar (array) --}}
@php
    $status = $result->health_status;
    $delta  = $previous ? round($result->overall_health_score - $previous->overall_health_score, 1) : null;
    $m      = $result->metrics;

    // Format angka gaya Indonesia: 1.234,5
    $fmt = fn (float $n, int $d = 1): string => number_format($n, $d, ',', '.');

    $metricRows = [
        ['Rasio lancar',              $fmt($m['current_ratio'], 2) . 'x',                         'Aset lancar dibagi liabilitas lancar'],
        ['Margin bersih',             $fmt($m['net_margin'] * 100) . '%',                         'Laba bersih sebagai persentase pendapatan'],
        ['Margin kotor',              $m['gross_margin'] === null ? 'Tidak berlaku' : $fmt($m['gross_margin'] * 100) . '%', 'Sisa pendapatan setelah HPP (tidak dinilai jika tanpa HPP)'],
        ['Rasio biaya operasional',   $fmt($m['operating_expense_ratio'] * 100) . '%',            'Biaya operasional sebagai persentase pendapatan'],
        ['Cadangan kas',              $fmt($m['cash_runway_months']) . ' bulan',                  'Kas dibagi biaya per bulan'],
        ['Hari penagihan piutang',    $fmt($m['dso'], 0) . ' hari',                               'Berapa lama pelanggan membayar'],
        ['Hari persediaan',           $fmt($m['dio'], 0) . ' hari',                               'Berapa lama stok mengendap sebelum terjual'],
        ['Hari pembayaran utang',     $fmt($m['dpo'], 0) . ' hari',                               'Berapa lama Anda membayar pemasok'],
        ['Siklus konversi kas',       $fmt($m['cash_conversion_cycle'], 0) . ' hari',             'Hari piutang + hari persediaan - hari utang'],
        ['Perputaran persediaan',     $m['inventory_turnover'] === null ? 'Tidak berlaku' : $fmt($m['inventory_turnover']) . 'x per tahun', 'HPP dibagi persediaan, disetahunkan'],
    ];

    $severity = [
        'critical' => ['border-rose-500',  'text-rose-700',  'Kritis'],
        'warning'  => ['border-amber-500', 'text-amber-700', 'Perhatian'],
        'info'     => ['border-sky-500',   'text-sky-700',   'Catatan'],
        'positive' => ['border-teal-600',  'text-teal-700',  'Kekuatan'],
    ];
    $priorityLabel = ['high' => 'Prioritas tinggi', 'medium' => 'Prioritas sedang', 'low' => 'Prioritas rendah'];
@endphp

{{-- Skor, pilar, dan radar --}}
<section class="grid gap-6 rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200 lg:grid-cols-5 lg:gap-10">
    <div class="lg:col-span-2">
        <p class="text-sm text-slate-500">Skor kesehatan total</p>
        <div class="mt-1 flex items-baseline gap-2">
            <span class="text-6xl font-semibold tracking-tight" style="color: {{ $status->hex() }}">{{ number_format($result->overall_health_score, 0) }}</span>
            <span class="text-slate-400">dari 100</span>
        </div>
        <div class="mt-3 flex flex-wrap items-center gap-3">
            <x-status-badge :status="$status" />
            @if ($delta !== null)
                <span class="text-sm {{ $delta >= 0 ? 'text-teal-700' : 'text-rose-700' }}">
                    {{ $delta >= 0 ? 'Naik' : 'Turun' }} {{ $fmt(abs($delta)) }} poin sejak {{ $previous->periodLabel() }}
                </span>
            @endif
        </div>
        <p class="mt-3 text-sm text-slate-600">{{ $status->summary() }}</p>

        <ul class="mt-6 space-y-4">
            @foreach ($result->pillars() as $pillar)
                @php $pillarStatus = \App\Enums\HealthStatus::fromScore($pillar['score']); @endphp
                <li>
                    <div class="flex justify-between text-sm">
                        <span class="font-medium">{{ $pillar['label'] }} <span class="font-normal text-slate-400">({{ number_format($pillar['weight'] * 100, 0) }}% dari total)</span></span>
                        <span class="font-semibold">{{ number_format($pillar['score'], 0) }}</span>
                    </div>
                    <div class="mt-1.5 h-2 overflow-hidden rounded-full bg-slate-100" role="progressbar" aria-valuenow="{{ round($pillar['score']) }}" aria-valuemin="0" aria-valuemax="100" aria-label="Skor {{ $pillar['label'] }}">
                        <div class="h-full rounded-full {{ $pillarStatus->barClass() }}" style="width: {{ $pillar['score'] }}%"></div>
                    </div>
                </li>
            @endforeach
        </ul>
    </div>

    <div class="lg:col-span-3">
        <x-radar-chart :payload="$radar" height="h-72 sm:h-96" />
    </div>
</section>

{{-- Temuan dan rekomendasi --}}
<section class="mt-8 grid gap-8 lg:grid-cols-2">
    <div>
        <h2 class="text-lg font-semibold">Temuan kami</h2>
        <ul class="mt-4 space-y-3">
            @forelse ($result->key_findings as $finding)
                @php [$border, $text, $tag] = $severity[$finding['severity']] ?? $severity['info']; @endphp
                <li class="border-l-4 {{ $border }} bg-white py-3 pl-4 pr-3 shadow-sm ring-1 ring-slate-200/70">
                    <p class="text-sm font-semibold"><span class="{{ $text }}">{{ $tag }}.</span> {{ $finding['title'] }}</p>
                    <p class="mt-1 text-sm text-slate-600">{{ $finding['message'] }}</p>
                </li>
            @empty
                <li class="text-sm text-slate-500">Tidak ada temuan penting untuk periode ini.</li>
            @endforelse
        </ul>
    </div>

    <div>
        <h2 class="text-lg font-semibold">Langkah berikutnya</h2>
        <ol class="mt-4 space-y-3">
            @foreach ($result->recommendations as $rec)
                <li class="rounded-lg bg-white p-4 shadow-sm ring-1 ring-slate-200/70">
                    <p class="text-xs font-medium text-slate-500">{{ $priorityLabel[$rec['priority']] ?? $rec['priority'] }}</p>
                    <p class="mt-1 text-sm">{{ $rec['action'] }}</p>
                </li>
            @endforeach
        </ol>
    </div>
</section>

{{-- Metrik --}}
<section class="mt-8">
    <h2 class="text-lg font-semibold">Angka di balik skor</h2>
    <dl class="mt-4 divide-y divide-slate-200 rounded-xl bg-white ring-1 ring-slate-200">
        @foreach ($metricRows as [$label, $value, $hint])
            <div class="flex flex-wrap items-baseline justify-between gap-x-6 px-4 py-3">
                <div>
                    <dt class="text-sm font-medium">{{ $label }}</dt>
                    <dd class="text-xs text-slate-500">{{ $hint }}</dd>
                </div>
                <dd class="text-sm font-semibold">{{ $value }}</dd>
            </div>
        @endforeach
    </dl>
</section>
