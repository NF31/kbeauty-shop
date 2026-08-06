<?php

namespace App\Infrastructure\Shared;

use App\Domain\Shared\Contracts\UnitOfWorkInterface;
use Closure;
use Illuminate\Support\Facades\DB;

/**
 * Implémentation {@see UnitOfWorkInterface} via `DB::transaction()` : garde
 * la couche Application indépendante d'Eloquent/Illuminate, pour que les
 * UseCases restent testables sans dépendre du framework de persistance.
 */
class DatabaseUnitOfWork implements UnitOfWorkInterface
{
    public function run(Closure $callback): mixed
    {
        return DB::transaction($callback);
    }
}
