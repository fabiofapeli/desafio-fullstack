<?php

namespace App\Http\Controllers;

use App\Http\Resources\PlanResource;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Src\Application\UseCases\DTO\ListPlansInputDto;
use Src\Application\UseCases\ListPlansUseCase;

class PlanController extends Controller
{
    public function index(ListPlansUseCase $useCase)
    {
        $result = $useCase->execute(new ListPlansInputDto());

        // $result é ListPlansOutputDto, então acessamos sua propriedade "items"
        return PlanResource::collection($result->items);
    }
}
