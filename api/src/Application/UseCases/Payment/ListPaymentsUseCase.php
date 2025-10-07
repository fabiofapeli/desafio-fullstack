<?php

namespace Src\Application\UseCases\Payment;

use Src\Application\UseCases\DTO\Payment\ListPaymentsInputDto;
use Src\Application\UseCases\DTO\Payment\ListPaymentsOutputDto;
use Src\Domain\Services\PaymentService;

class ListPaymentsUseCase
{
    public function __construct(private PaymentService $paymentService) {}

    public function execute(ListPaymentsInputDto $input): ListPaymentsOutputDto
    {
        $history = $this->paymentService->getPaymentHistory($input->userId);
        return new ListPaymentsOutputDto($history);
    }
}
