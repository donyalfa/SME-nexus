<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent;

use App\Models\Sme;
use App\Repositories\Contracts\SmeRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

final class EloquentSmeRepository implements SmeRepositoryInterface
{
    public function listForUser(int $userId): Collection
    {
        return Sme::query()->where('user_id', $userId)->orderBy('business_name')->get();
    }

    public function findForUser(int $smeId, int $userId): ?Sme
    {
        return Sme::query()->whereKey($smeId)->where('user_id', $userId)->first();
    }

    public function create(array $attributes): Sme
    {
        return Sme::query()->create($attributes);
    }
}
