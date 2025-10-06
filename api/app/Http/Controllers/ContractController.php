<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Src\Application\UseCases\DTO\Subscriber\ChangePlanInputDto;
use Src\Application\UseCases\DTO\Subscriber\GetActivePlanInputDto;
use Src\Application\UseCases\DTO\Subscriber\RenewPlanInputDto;
use Src\Application\UseCases\DTO\Subscriber\SubscriberPlanInputDto;
use Src\Application\UseCases\Subscriber\ChangePlanUseCase;
use Src\Application\UseCases\Subscriber\GetActivePlanUseCase;
use Src\Application\UseCases\Subscriber\RenewPlanUseCase;
use Src\Application\UseCases\Subscriber\SubscribePlanUseCase;
use Src\Domain\Exceptions\BusinessException;

class ContractController extends Controller
{

    private SubscribePlanUseCase $subscribePlanUseCase;
    private RenewPlanUseCase $renewPlanUseCase;
    private ChangePlanUseCase $changePlanUseCase;

    public function __construct(
        SubscribePlanUseCase $subscribePlanUseCase,
        RenewPlanUseCase $renewPlanUseCase,
        ChangePlanUseCase $changePlanUseCase
    )
    {
        $this->subscribePlanUseCase = $subscribePlanUseCase;
        $this->renewPlanUseCase = $renewPlanUseCase;
        $this->changePlanUseCase = $changePlanUseCase;
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'plan_id' => 'required|exists:plans,id',
            ]);

            $userId = 1; // simula usuário logado

            $inputDto = new SubscriberPlanInputDto($userId, $validated['plan_id']);
            $outputDto = $this->subscribePlanUseCase->execute($inputDto);

            return response()->json([
                'plan' => $outputDto->plan,
                'payment' => $outputDto->payment,
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

    public function renew(): JsonResponse
    {
        try {

            $userId = 1; // simulando usuário logado
            $inputDto = new RenewPlanInputDto($userId);
            $outputDto = $this->renewPlanUseCase->execute($inputDto);

            return response()->json([
                'contract' => $outputDto->contract,
                'payment' => $outputDto->payment,
            ], Response::HTTP_OK);

        } catch (BusinessException $e) {
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

    public function changePlan(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'new_plan_id' => 'required|exists:plans,id',
            ]);

            $userId = 1; // Usuário simulado
            $inputDto = new ChangePlanInputDto($userId, $validated['new_plan_id']);
            $outputDto = $this->changePlanUseCase->execute($inputDto);

            return response()->json([
                'contract' => $outputDto->contract,
                'payment' => $outputDto->payment,
            ], Response::HTTP_CREATED);

        } catch (BusinessException $e) {
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

    public function getActive(GetActivePlanUseCase $useCase): JsonResponse
    {
        try {
            $userId = 1; // usuário logado simulado
            $input = new GetActivePlanInputDto($userId);
            $output = $useCase->execute($input);

            return response()->json([
                'contract' => $output->contract,
                'plan' => $output->plan,
                'payments' => $output->payments,
            ], Response::HTTP_OK);

        } catch (BusinessException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], Response::HTTP_NOT_FOUND);

        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Server Error',
                'error' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

}

