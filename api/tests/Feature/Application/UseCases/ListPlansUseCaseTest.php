<?php

namespace Application\UseCases;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Src\Application\UseCases\ListPlansUseCase;
use Src\Infra\Eloquent\PlanModel;
use Tests\TestCase;

class ListPlansUseCaseTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_should_return_all_plans()
    {
        // Arrange
        PlanModel::factory()->create(['description' => 'Plano Bronze']);
        PlanModel::factory()->create(['description' => 'Plano Prata']);
        PlanModel::factory()->create(['description' => 'Plano Ouro']);

        $useCase = new ListPlansUseCase();

        // Act
        $result = $useCase->execute();

        // Assert
        $this->assertCount(3, $result);
        $this->assertEquals('Plano Bronze', $result[0]['description']);
    }
}
