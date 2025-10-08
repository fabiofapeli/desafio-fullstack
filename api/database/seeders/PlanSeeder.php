<?php

namespace Database\Seeders;


use Illuminate\Database\Seeder;
use Src\Infra\Eloquent\PlanModel;

class PlanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $plans = [
            [
                'description' => 'Individual',
                'numberOfClients' => 1,
                'price' => 9.90,
                'gigabytesStorage' => 1,
            ],
            [
                'description' => 'Até 10 vistorias',
                'numberOfClients' => 10,
                'price' => 87.00,
                'gigabytesStorage' => 10,
            ],
            [
                'description' => 'Até 25 vistorias',
                'numberOfClients' => 25,
                'price' => 197.00,
                'gigabytesStorage' => 25,
            ],
            [
                'description' => 'Até 50 vistorias',
                'numberOfClients' => 50,
                'price' => 347.00,
                'gigabytesStorage' => 50,
            ],
            [
                'description' => 'Até 100 vistorias',
                'numberOfClients' => 100,
                'price' => 497.00,
                'gigabytesStorage' => 100,
            ],
            [
                'description' => 'Até 250 vistorias',
                'numberOfClients' => 250,
                'price' => 797.00,
                'gigabytesStorage' => 250,
            ]
        ];

        foreach ($plans as $plan) {
            PlanModel::create($plan);
        }
    }
}
