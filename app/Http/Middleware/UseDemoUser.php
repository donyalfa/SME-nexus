<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * SEMENTARA: melewati login dengan memakai satu "demo user" selama pengembangan fitur.
 * Seluruh kode lain tetap memakai $request->user(), jadi nanti cukup mengganti
 * middleware ini dengan 'auth' di routes/web.php saat autentikasi sungguhan dipasang.
 */
final class UseDemoUser
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! Auth::check()) {
            abort_unless(config('nexus.demo_mode'), 401, 'Authentication required.');

            $user = User::query()->firstOrCreate(
                ['email' => 'demo@sme-nexus.test'],
                ['name' => 'Demo Owner', 'password' => Str::random(40)],
            );

            Auth::login($user);
        }

        return $next($request);
    }
}
