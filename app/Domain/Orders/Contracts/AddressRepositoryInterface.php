<?php

namespace App\Domain\Orders\Contracts;

use App\Models\Address;

interface AddressRepositoryInterface
{
    public function find(int $id): ?Address;

    public function findOrFail(int $id): Address;

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Address;
}
