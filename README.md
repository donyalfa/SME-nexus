# SME NEXUS: DETECT + UNDERSTAND (Laravel 12)

Fokus pada fitur. Login belum dipasang: semua route memakai demo user otomatis (`UseDemoUser`).

```bash
laravel new sme-nexus            # pilih "none" untuk starter kit
# salin isi folder ini ke proyek (gabungkan routes/web.php dan AppServiceProvider.php bila sudah berubah), lalu:
npm install alpinejs chart.js
php artisan migrate
npm run dev
php artisan serve                # buka http://localhost:8000
php artisan test
```

## Nanti saat memasang login
1. Pasang starter kit / Fortify.
2. Di `routes/web.php`, ganti `UseDemoUser::class` dengan `'auth'`.
3. Set `NEXUS_DEMO_MODE=false`, lalu hapus `app/Http/Middleware/UseDemoUser.php`.

## Tuning
- `config/nexus.php`: bobot pilar, ambang status, benchmark per industri, ambang aturan.
- `UnderstandService::RECOMMENDATIONS`: teks rekomendasi per kode temuan.

## Alur
`POST /diagnostics` -> `StoreFinancialRecordRequest` -> `DiagnosticPipeline` (transaksi)
-> `DetectService` -> `UnderstandService::diagnose()` -> `GET /diagnostics/{id}` (`?format=json` untuk JSON radar).

## Bahasa
Antarmuka berbahasa Indonesia (`NEXUS_LOCALE=id`, default). Pesan validasi ada di `lang/id/validation.php`.
Temuan dan rekomendasi disimpan sebagai teks saat diagnosis dibuat, jadi data lama tetap berbahasa Inggris.
Setelah pindah bahasa, jalankan `php artisan migrate:fresh` untuk data uji yang bersih.
