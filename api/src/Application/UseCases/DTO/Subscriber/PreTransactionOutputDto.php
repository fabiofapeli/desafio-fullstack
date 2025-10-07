<?php

namespace Src\Application\UseCases\DTO\Subscriber;

class PreTransactionOutputDto
{
    public function __construct(
        public array $plan,
        public string $action,
        public ?array $renewalWindow = null, // ['available_from' => 'YYYY-MM-DD', 'expiration_date' => 'YYYY-MM-DD']
        public ?float $credit = null,
        public ?float $price = null
    ) {}

    public function toArray(): array
    {
        return [
            'plan' => $this->plan,
            'action' => $this->action,
            'renewal_window' => $this->renewalWindow,
            'credit' => $this->credit,
            'price' => $this->price,
        ];
    }
}
