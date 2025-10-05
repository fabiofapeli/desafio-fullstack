<?php

namespace Src\Domain\Entities;

use Src\Domain\Entities\Enums\PaymentStatus;

class Payment
{
    public function __construct(
        public int $id,
        public int $contract_id,
        public string $type,
        public float $price,
        public ?string $payment_at,
        public PaymentStatus $status
    ) {}
}
