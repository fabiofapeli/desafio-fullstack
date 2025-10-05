<?php

namespace Src\Domain\Entities;

use Src\Domain\Entities\Enums\ContractStatus;

class Contract
{
    public function __construct(
        public int $id,
        public int $user_id,
        public int $plan_id,
        public string $started_at,
        public ?string $expiration_date,
        public ?string $ended_at,
        public ContractStatus $status
    ) {}
}
