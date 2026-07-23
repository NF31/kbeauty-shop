<?php

namespace App\Infrastructure\Shared;

use App\Domain\Shared\Contracts\UnitOfWorkInterface;
use Closure;
use Illuminate\Support\Facades\DB;

class DatabaseUnitOfWork implements UnitOfWorkInterface
{
    public function run(Closure $callback): mixed
    {
        return DB::transaction($callback);
    }
}
