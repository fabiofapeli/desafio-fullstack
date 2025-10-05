<?php

namespace Src\Application\UseCases;

use Src\Application\UseCases\DTO\ListPlansInputDto;
use Src\Application\UseCases\DTO\ListPlansOutputDto;
use Src\Infra\Eloquent\PlanModel;

class ListPlansUseCase
{
    public function execute(ListPlansInputDto $input): ListPlansOutputDto
    {
        return new ListPlansOutputDto(
            PlanModel::all()->toArray()
        );
    }
}
