<?php

namespace App\Domain\Payments;

final readonly class RefundResult
{
    public function __construct(
        public string $id,
        public string $status,
    ) {}
}
