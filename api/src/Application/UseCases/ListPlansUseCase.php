<?php

namespace Src\Application\UseCases;

use Src\Infra\Eloquent\PlanModel;

class ListPlansUseCase
{
    public function execute(): array
    {
        return PlanModel::all()->toArray();
    }
}
