<?php

namespace Src\Application\UseCases\DTO\Subscriber;

class SubscriberPlanOutputDTO
{
    public function __construct(
        public array $plan,
        public array $payment,
    ){

    }
}
