<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Src\Application\UseCases\ListPlansUseCase;

class PlanController extends Controller
{

    public function index(ListPlansUseCase $useCase): JsonResponse
    {
        return response()->json($useCase->execute());
    }

}
