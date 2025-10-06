<?php

namespace Tests\Feature\Http\Controllers;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Src\Infra\Eloquent\UserModel;
use Tests\TestCase;

class UserControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_api_returns_user_data()
    {
        UserModel::factory()->create([
            'name' => 'João Silva',
            'email' => 'joao@example.com',
        ]);

        $response = $this->getJson("/api/user");

        $response->assertStatus(200)
            ->assertJsonFragment([
                'name' => 'João Silva',
                'email' => 'joao@example.com',
            ]);
    }

    public function test_api_returns_404_if_user_not_found()
    {
        $response = $this->getJson('/api/user');

        $response->assertStatus(404)
            ->assertJsonFragment([
                'message' => 'Usuário não encontrado.',
            ]);
    }
}
