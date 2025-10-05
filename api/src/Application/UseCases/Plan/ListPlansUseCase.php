<?php

namespace Src\Application\UseCases\Plan;

use Src\Application\UseCases\DTO\Plan\ListPlansInputDto;
use Src\Application\UseCases\DTO\Plan\ListPlansOutputDto;
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
