<?php

namespace Tests\Unit\Application\UseCases;

use PHPUnit\Framework\TestCase;
use Src\Application\UseCases\DTO\ListPlansInputDto;
use Src\Application\UseCases\ListPlansUseCase;
use Src\Infra\Eloquent\PlanModel;
use Mockery;

class ListPlansUseCaseTest extends TestCase
{
    /** @test */
    public function it_returns_all_plans_without_touching_database()
    {
        // Arrange: simulamos o retorno do Eloquent
        $fakePlans = collect([
            [
                'id' => 1,
                'description' => 'Plano Bronze',
                'numberOfClients' => 50,
                'gigabytesStorage' => 10,
                'price' => 49.90,
                'active' => true,
            ],
           [
                'id' => 2,
                'description' => 'Plano Prata',
                'numberOfClients' => 150,
                'gigabytesStorage' => 30,
                'price' => 99.90,
                'active' => true,
            ],
        ]);

        // Criamos um mock da classe Eloquent
        $mock = Mockery::mock('alias:' . PlanModel::class);
        $mock->shouldReceive('all')->once()->andReturn($fakePlans);

        // Act
        $useCase = new ListPlansUseCase();
        $result = $useCase->execute(
            new ListPlansInputDto()
        )->items;

        // Assert
        $this->assertCount(2, $result);
        $this->assertEquals('Plano Bronze', $result[0]['description']);
    }

    protected function tearDown(): void
    {
        Mockery::close(); // importante para limpar os mocks
        parent::tearDown();
    }
}
