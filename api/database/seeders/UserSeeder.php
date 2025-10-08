<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Src\Infra\Eloquent\UserModel;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        UserModel::create([
            'name' => 'Usuário da Silva',
            'email' => 'usuario@silva.com',
        ]);
    }
}
