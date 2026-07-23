<?php

namespace App\Domain\Shared\Contracts;

use Closure;

interface UnitOfWorkInterface
{
    /**
     * @template TReturn
     *
     * @param  Closure(): TReturn  $callback
     * @return TReturn
     */
    public function run(Closure $callback): mixed;
}
