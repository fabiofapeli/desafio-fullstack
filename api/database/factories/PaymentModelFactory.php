<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Src\Infra\Eloquent\PaymentModel;
use Src\Infra\Eloquent\ContractModel;
use Carbon\Carbon;

class PaymentModelFactory extends Factory
{
    protected $model = PaymentModel::class;

    public function definition(): array
    {
        return [
            'contract_id' => ContractModel::factory(),
            'action' => $this->faker->randomElement(['purchase', 'renewal']),
            'type' => 'pix',
            'plan_value' => $this->faker->randomFloat(2, 50, 200),
            'price' => $this->faker->randomFloat(2, 50, 200),
            'credit' => $this->faker->randomFloat(2, 0, 50),
            'payment_at' => Carbon::now(),
            'status' => 'paid',
        ];
    }
}
