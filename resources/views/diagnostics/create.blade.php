@extends('layouts.nexus')

@section('title', 'Diagnosis baru')

@section('content')
    <div class="max-w-3xl">
        <h1 class="text-2xl font-semibold tracking-tight">Masukkan data keuangan satu periode</h1>
        <p class="mt-1 text-slate-600">Gunakan angka dari laporan laba rugi dan neraca Anda. Rasio dihitung dan bisnis Anda langsung didiagnosis begitu data disimpan.</p>
    </div>

    @if ($errors->any())
        <div role="alert" class="mt-6 max-w-3xl rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
            Perbaiki {{ $errors->count() }} masalah di bawah, lalu simpan lagi.
        </div>
    @endif

    <form method="POST" action="{{ route('diagnostics.store') }}" x-data="detectForm(@js(old()))" class="mt-8 grid gap-8 lg:grid-cols-[minmax(0,1fr)_16rem]">
        @csrf

        <div class="space-y-10">
            {{-- Business --}}
            <section class="space-y-4">
                <h2 class="text-lg font-semibold">Bisnis</h2>

                @if ($smes->isNotEmpty())
                    <div>
                        <label for="sme_id" class="block text-sm font-medium text-slate-700">Bisnis</label>
                        <select id="sme_id" name="sme_id" required
                                class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-teal-600 focus:outline-none focus:ring-2 focus:ring-teal-600/25">
                            @foreach ($smes as $sme)
                                <option value="{{ $sme->id }}" @selected((int) old('sme_id', request('sme', $smes->first()->id)) === $sme->id)>
                                    {{ $sme->business_name }} ({{ $sme->industry_sector->label() }})
                                </option>
                            @endforeach
                        </select>
                        @error('sme_id') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                    </div>
                @else
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label for="business_name" class="block text-sm font-medium text-slate-700">Nama bisnis</label>
                            <input type="text" id="business_name" name="business_name" value="{{ old('business_name') }}" required maxlength="150"
                                   class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-teal-600 focus:outline-none focus:ring-2 focus:ring-teal-600/25">
                            @error('business_name') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="industry_sector" class="block text-sm font-medium text-slate-700">Sektor industri</label>
                            <select id="industry_sector" name="industry_sector" required
                                    class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-teal-600 focus:outline-none focus:ring-2 focus:ring-teal-600/25">
                                <option value="" disabled @selected(! old('industry_sector'))>Pilih sektor</option>
                                @foreach ($sectors as $sector)
                                    <option value="{{ $sector->value }}" @selected(old('industry_sector') === $sector->value)>{{ $sector->label() }}</option>
                                @endforeach
                            </select>
                            <p class="mt-1 text-xs text-slate-500">Skor dibandingkan dengan benchmark sektor ini.</p>
                            @error('industry_sector') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                        </div>
                    </div>
                @endif
            </section>

            {{-- Period --}}
            <section class="space-y-4">
                <h2 class="text-lg font-semibold">Periode laporan</h2>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="period_type" class="block text-sm font-medium text-slate-700">Panjang periode</label>
                        <select id="period_type" name="period_type" required
                                class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-teal-600 focus:outline-none focus:ring-2 focus:ring-teal-600/25">
                            @foreach ($periodTypes as $type)
                                <option value="{{ $type->value }}" @selected(old('period_type', 'monthly') === $type->value)>{{ $type->label() }}</option>
                            @endforeach
                        </select>
                        <p class="mt-1 text-xs text-slate-500">Pendapatan, HPP, dan biaya di bawah harus mencakup seluruh periode ini.</p>
                        @error('period_type') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="period_date" class="block text-sm font-medium text-slate-700">Tanggal akhir periode</label>
                        <input type="date" id="period_date" name="period_date" value="{{ old('period_date') }}" max="{{ now()->toDateString() }}" required
                               class="mt-1 block w-full rounded-lg border {{ $errors->has('period_date') ? 'border-rose-400' : 'border-slate-300' }} bg-white px-3 py-2 text-sm shadow-sm focus:border-teal-600 focus:outline-none focus:ring-2 focus:ring-teal-600/25">
                        <p class="mt-1 text-xs text-slate-500">Angka neraca dihitung per tanggal ini.</p>
                        @error('period_date') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                    </div>
                </div>
            </section>

            {{-- Income statement --}}
            <section class="space-y-4">
                <h2 class="text-lg font-semibold">Laporan laba rugi</h2>
                <div class="grid gap-4 sm:grid-cols-2">
                    <x-money-input name="revenue" label="Pendapatan" hint="Total penjualan selama periode. Harus lebih dari 0." />
                    <x-money-input name="cogs" label="Harga pokok penjualan (HPP)" hint="Biaya langsung dari barang atau jasa yang terjual. Isi 0 jika tidak ada." />
                    <x-money-input name="operating_expenses" label="Biaya operasional" hint="Gaji, sewa, pemasaran, listrik, dan sejenisnya." />
                </div>
            </section>

            {{-- Balance sheet --}}
            <section class="space-y-4">
                <h2 class="text-lg font-semibold">Neraca</h2>
                <div class="grid gap-4 sm:grid-cols-2">
                    <x-money-input name="current_assets" label="Aset lancar" hint="Semua yang diperkirakan menjadi kas dalam satu tahun." />
                    <x-money-input name="current_liabilities" label="Liabilitas lancar" hint="Utang dan tagihan yang jatuh tempo dalam satu tahun." />
                    <x-money-input name="cash_balance" label="Saldo kas" hint="Kas dan saldo bank." />
                    <x-money-input name="receivables" label="Piutang" hint="Uang pelanggan yang belum dibayarkan kepada Anda." />
                    <x-money-input name="payables" label="Utang dagang" hint="Uang yang masih harus Anda bayar ke pemasok." />
                    <x-money-input name="inventory_value" label="Nilai persediaan" hint="Nilai stok barang. Isi 0 jika tidak ada." />
                </div>
                <p x-show="assetsMismatch" x-cloak class="rounded-lg bg-amber-50 px-3 py-2 text-sm text-amber-800">
                    Kas + piutang + persediaan lebih besar dari aset lancar. Periksa kembali angkanya.
                </p>
            </section>

            <button type="submit"
                    class="rounded-lg bg-teal-700 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-teal-800 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-teal-700">
                Simpan dan diagnosis
            </button>
        </div>

        {{-- Live preview --}}
        <aside class="lg:sticky lg:top-6 lg:self-start">
            <div class="rounded-xl border border-slate-200 bg-white p-4">
                <h2 class="text-sm font-semibold">Cek cepat</h2>
                <dl class="mt-3 space-y-3 text-sm">
                    <div class="flex justify-between"><dt class="text-slate-600">Rasio lancar</dt><dd class="font-semibold" x-text="currentRatio"></dd></div>
                    <div class="flex justify-between"><dt class="text-slate-600">Margin bersih</dt><dd class="font-semibold" x-text="netMargin"></dd></div>
                </dl>
                <p class="mt-3 text-xs text-slate-500">Pratinjau saat Anda mengetik. Diagnosis lengkap berjalan setelah data disimpan.</p>
            </div>
        </aside>
    </form>
@endsection
