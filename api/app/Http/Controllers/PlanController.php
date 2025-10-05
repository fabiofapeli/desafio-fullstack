<?php

namespace App\Http\Controllers;

use App\Http\Resources\PlanResource;
use Src\Application\UseCases\DTO\Plan\ListPlansInputDto;
use Src\Application\UseCases\Plan\ListPlansUseCase;

class PlanController extends Controller
{
    public function index(ListPlansUseCase $useCase)
    {
        $result = $useCase->execute(new ListPlansInputDto());

        // $result é ListPlansOutputDto, então acessamos sua propriedade "items"
        return PlanResource::collection($result->items);
    }
}
