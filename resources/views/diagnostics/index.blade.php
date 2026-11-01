@extends('layouts.nexus')

@section('title', 'Dasbor')

@section('content')
    @if ($sme === null)
        <div class="mx-auto max-w-xl rounded-2xl border border-dashed border-slate-300 bg-white p-10 text-center">
            <h1 class="text-xl font-semibold">Tambahkan periode keuangan pertama Anda</h1>
            <p class="mt-2 text-sm text-slate-600">Masukkan angka laba rugi dan neraca terbaru untuk melihat skor kesehatan, titik lemah, dan apa yang perlu diperbaiki lebih dulu.</p>
            <a href="{{ route('diagnostics.create') }}" class="mt-6 inline-block rounded-lg bg-teal-700 px-5 py-2.5 text-sm font-semibold text-white hover:bg-teal-800">Mulai diagnosis</a>
        </div>
    @else
        <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
            <div>
                <h1 class="text-2xl font-semibold tracking-tight">{{ $sme->business_name }}</h1>
                <p class="text-sm text-slate-500">
                    {{ $sme->industry_sector->label() }}
                    @if ($latest) , periode terbaru {{ $latest->periodLabel() }} @endif
                </p>
            </div>

            <div class="flex items-center gap-3">
                @if ($smes->count() > 1)
                    <form method="GET" action="{{ route('diagnostics.index') }}">
                        <label for="sme" class="sr-only">Bisnis</label>
                        <select id="sme" name="sme" onchange="this.form.submit()" class="rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-sm">
                            @foreach ($smes as $option)
                                <option value="{{ $option->id }}" @selected($option->id === $sme->id)>{{ $option->business_name }}</option>
                            @endforeach
                        </select>
                    </form>
                @endif
                <a href="{{ route('diagnostics.create', ['sme' => $sme->id]) }}" class="text-sm font-medium text-teal-700 hover:text-teal-900">Tambah periode</a>
            </div>
        </div>

        @if ($latest === null)
            <div class="rounded-2xl border border-dashed border-slate-300 bg-white p-10 text-center">
                <p class="text-sm text-slate-600">Bisnis ini belum punya diagnosis.</p>
                <a href="{{ route('diagnostics.create', ['sme' => $sme->id]) }}" class="mt-4 inline-block rounded-lg bg-teal-700 px-5 py-2.5 text-sm font-semibold text-white hover:bg-teal-800">Masukkan data keuangan</a>
            </div>
        @else
            @include('diagnostics.partials.report', ['result' => $latest, 'previous' => $previous, 'radar' => $radar])

            <section class="mt-8">
                <h2 class="text-lg font-semibold">Riwayat</h2>
                <div class="mt-4 overflow-x-auto rounded-xl bg-white ring-1 ring-slate-200">
                    <table class="min-w-full text-left text-sm">
                        <thead class="border-b border-slate-200 text-slate-500">
                            <tr>
                                <th scope="col" class="px-4 py-2 font-medium">Periode</th>
                                <th scope="col" class="px-4 py-2 font-medium">Skor</th>
                                <th scope="col" class="px-4 py-2 font-medium">Status</th>
                                <th scope="col" class="px-4 py-2"><span class="sr-only">Buka</span></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($history as $row)
                                <tr>
                                    <td class="px-4 py-2">{{ $row->financialRecord->period_date->translatedFormat('d M Y') }} <span class="text-slate-400">({{ $row->financialRecord->period_type->label() }})</span></td>
                                    <td class="px-4 py-2 font-semibold">{{ number_format($row->overall_health_score, 0) }}</td>
                                    <td class="px-4 py-2"><x-status-badge :status="$row->health_status" /></td>
                                    <td class="px-4 py-2 text-right"><a href="{{ route('diagnostics.show', $row->id) }}" class="font-medium text-teal-700 hover:text-teal-900">Lihat laporan</a></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>
        @endif
    @endif
@endsection
