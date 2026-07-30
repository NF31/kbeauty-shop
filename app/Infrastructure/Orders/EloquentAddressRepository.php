<?php

namespace App\Infrastructure\Orders;

use App\Domain\Orders\Contracts\AddressRepositoryInterface;
use App\Models\Address;

class EloquentAddressRepository implements AddressRepositoryInterface
{
    public function find(int $id): ?Address
    {
        return Address::query()->find($id);
    }

    public function findOrFail(int $id): Address
    {
        return Address::query()->findOrFail($id);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Address
    {
        return Address::query()->create($data);
    }
}
