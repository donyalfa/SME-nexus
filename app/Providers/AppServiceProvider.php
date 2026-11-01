<?php

declare(strict_types=1);

namespace App\Providers;

use App\Repositories\Contracts\DiagnosticResultRepositoryInterface;
use App\Repositories\Contracts\FinancialRecordRepositoryInterface;
use App\Repositories\Contracts\SmeRepositoryInterface;
use App\Repositories\Eloquent\EloquentDiagnosticResultRepository;
use App\Repositories\Eloquent\EloquentFinancialRecordRepository;
use App\Repositories\Eloquent\EloquentSmeRepository;
use Illuminate\Support\Carbon;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /** Interface => implementation bindings for the Service-Repository pattern. */
    public array $bindings = [
        SmeRepositoryInterface::class              => EloquentSmeRepository::class,
        FinancialRecordRepositoryInterface::class  => EloquentFinancialRecordRepository::class,
        DiagnosticResultRepositoryInterface::class => EloquentDiagnosticResultRepository::class,
    ];

    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $locale = (string) config('nexus.locale', 'id');

        $this->app->setLocale($locale);
        Carbon::setLocale($locale); // nama bulan/hari dalam Bahasa Indonesia
    }
}
