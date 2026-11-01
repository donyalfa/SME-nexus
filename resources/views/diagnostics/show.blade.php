@extends('layouts.nexus')

@section('title', 'Laporan ' . $result->sme->business_name)

@section('content')
    <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
        <div>
            <a href="{{ route('diagnostics.index', ['sme' => $result->sme_id]) }}" class="text-sm font-medium text-teal-700 hover:text-teal-900">Kembali ke dasbor</a>
            <h1 class="mt-1 text-2xl font-semibold tracking-tight">{{ $result->sme->business_name }}</h1>
            <p class="text-sm text-slate-500">
                Laporan {{ strtolower($result->financialRecord->period_type->label()) }} untuk periode yang berakhir {{ $result->financialRecord->period_date->translatedFormat('d M Y') }}
            </p>
        </div>
        <a href="{{ route('diagnostics.show', ['id' => $result->id, 'format' => 'json']) }}" class="text-sm font-medium text-slate-600 hover:text-ink">Lihat sebagai JSON</a>
    </div>

    @include('diagnostics.partials.report', ['result' => $result, 'previous' => $previous, 'radar' => $radar])

    <section class="mt-8 rounded-xl bg-white p-4 text-sm text-slate-600 ring-1 ring-slate-200">
        <h2 class="font-semibold text-ink">Cara skor dihitung</h2>
        <p class="mt-1">
            Setiap pilar diberi skor 0 sampai 100 terhadap benchmark sektor {{ $result->sme->industry_sector->label() }}. Skor total membobotnya:
            @foreach ($result->pillars() as $pillar)
                {{ strtolower($pillar['label']) }} {{ number_format($pillar['weight'] * 100, 0) }}%{{ ! $loop->last ? ($loop->remaining === 1 ? ' dan ' : ', ') : '' }}
            @endforeach
            (versi penilaian {{ $result->scoring_version }}).
        </p>
    </section>
@endsection
