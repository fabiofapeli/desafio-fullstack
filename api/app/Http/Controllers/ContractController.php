<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Src\Application\UseCases\SubscribePlanUseCase;
use Src\Domain\Exceptions\BusinessException;

class ContractController extends Controller
{
    public function store(Request $request, SubscribePlanUseCase $useCase)
    {
        try {
            $validated = $request->validate([
                'plan_id' => 'required|exists:plans,id',
            ]);

            $userId = 1; // Usuário fixo (simulando logado)

            $contract = $useCase->execute($userId, $validated['plan_id']);

            return response()->json($contract, 201);

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

