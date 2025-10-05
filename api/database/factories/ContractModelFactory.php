<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Src\Infra\Eloquent\ContractModel;
use Src\Infra\Eloquent\PlanModel;
use Src\Infra\Eloquent\UserModel;
use Carbon\Carbon;

class ContractModelFactory extends Factory
{
    protected $model = ContractModel::class;

    public function definition(): array
    {
        return [
            'user_id' => UserModel::factory(),
            'plan_id' => PlanModel::factory(),
            'started_at' => Carbon::now(),
            'expiration_date' => Carbon::now()->addMonth(),
            'ended_at' => null,
            'status' => 'active',
        ];
    }
}
