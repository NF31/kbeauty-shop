<?php

namespace App\Domain\Shared\Contracts;

use App\Models\User;
use Illuminate\Support\Collection;

interface UserRepositoryInterface
{
    /**
     * @return Collection<int, User>
     */
    public function admins(): Collection;
}
