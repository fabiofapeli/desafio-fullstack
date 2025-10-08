<?php

namespace Src\Application\UseCases\Subscriber;

use Carbon\Carbon;
use Src\Application\UseCases\DTO\Subscriber\RenewPlanInputDto;
use Src\Application\UseCases\DTO\Subscriber\RenewPlanOutputDto;
use Src\Domain\Entities\Enums\PaymentAction;
use Src\Domain\Exceptions\BusinessException;
use Src\Domain\Services\ContractService;

class RenewPlanUseCase
{
    public function __construct(private ContractService $service) {}

    /**
     * @throws BusinessException
     */
    public function execute(RenewPlanInputDto $input): RenewPlanOutputDto
    {
        $active = $this->service->getActivePlan($input->userId);
        if (!$active) {
            throw new BusinessException('Usuário não possui contrato ativo para renovação.');
        }

        // janela de renovação (regra calculada no service)
        $check = $this->service->checkRenewalAllowed($active);
        if (!$check['allowed']) {
            throw new BusinessException($check['reason'] ?? 'Renovação não permitida.');
        }

        // persiste: adiciona +1 mês a partir da expiração atual
        $active->expiration_date = $this->service
            ->computeNextMonthFrom(Carbon::parse($active->expiration_date));
        $active->next_renewal_available_at = $this->service
            ->computeNextRenewalAllowed(Carbon::parse($active->expiration_date));
        $active->save();

        $payment = $active->payments()->create([
            'type'        => 'pix',
            'plan_value'  => $active->plan->price,
            'price'       => $active->plan->price,
            'action'      => PaymentAction::renewal,
            'credit'      => 0,
            'payment_at'  => Carbon::now(),
            'status'      => 'paid',
        ]);

        return new RenewPlanOutputDto(
            contract: $active->toArray(),
            payment : $payment->toArray()
        );
    }
}
