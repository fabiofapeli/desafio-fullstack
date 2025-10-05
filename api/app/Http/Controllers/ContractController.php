<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Src\Application\UseCases\DTO\Subscriber\SubscriberPlanInputDto;
use Src\Application\UseCases\Subscriber\SubscribePlanUseCase;
use Src\Domain\Exceptions\BusinessException;

class ContractController extends Controller
{
    public function store(Request $request, SubscribePlanUseCase $useCase)
    {
        try {
            $validated = $request->validate([
                'plan_id' => 'required|exists:plans,id',
            ]);

            $userId = 1; // simula usuário logado

            $inputDto = new SubscriberPlanInputDto($userId, $validated['plan_id']);
            $outputDto = $useCase->execute($inputDto);

            return response()->json([
                'plan' => $outputDto->plan,
                'payments' => $outputDto->payment,
            ], Response::HTTP_CREATED);

        }
        catch (BusinessException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], Response::HTTP_CONFLICT);

        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Server Error',
                'error' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

    }
}

