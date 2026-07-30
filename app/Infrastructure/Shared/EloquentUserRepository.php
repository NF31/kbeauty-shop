<?php

namespace App\Infrastructure\Shared;

use App\Domain\Shared\Contracts\UserRepositoryInterface;
use App\Models\User;
use Illuminate\Support\Collection;

class EloquentUserRepository implements UserRepositoryInterface
{
    /**
     * @return Collection<int, User>
     */
    public function admins(): Collection
    {
        return User::role('admin')->get();
    }
}
