<?php

declare(strict_types=1);

use App\Http\Controllers\SmeDiagnosticController;
use App\Http\Middleware\UseDemoUser;
use Illuminate\Support\Facades\Route;

// TODO (nanti): ganti UseDemoUser::class dengan 'auth' ketika login sudah dipasang.
Route::middleware(UseDemoUser::class)->group(function (): void {
    Route::redirect('/', '/diagnostics');

    Route::resource('diagnostics', SmeDiagnosticController::class)
        ->only(['index', 'create', 'store', 'show'])
        ->parameters(['diagnostics' => 'id']);
});
