<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Src\Application\UseCases\DTO\Payment\ListPaymentsInputDto;
use Src\Application\UseCases\Payment\ListPaymentsUseCase;

class PaymentController extends Controller
{
    public function __construct(
        private ListPaymentsUseCase $listPaymentsUseCase
    ) {}

    public function history(): JsonResponse
    {
        $userId = 1; // Usuário simulado

        $output = $this->listPaymentsUseCase->execute(
            new ListPaymentsInputDto($userId)
        );

        return response()->json($output->payments, Response::HTTP_OK);
    }
}
