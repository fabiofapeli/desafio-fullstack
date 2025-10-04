<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Src\Infra\Eloquent\PlanModel;

class PlanModelFactory extends Factory
{
    protected $model = PlanModel::class;

    public function definition(): array
    {
        return [
            'description' => $this->faker->word(),
            'numberOfClients' => $this->faker->numberBetween(10, 500),
            'gigabytesStorage' => $this->faker->numberBetween(5, 100),
            'price' => $this->faker->randomFloat(2, 50, 500),
            'active' => $this->faker->boolean(),
        ];
    }
}
