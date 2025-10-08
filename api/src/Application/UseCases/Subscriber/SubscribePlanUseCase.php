<?php

namespace Src\Application\UseCases\Subscriber;

use Carbon\Carbon;
use Src\Application\UseCases\DTO\Subscriber\SubscriberPlanInputDto;
use Src\Application\UseCases\DTO\Subscriber\SubscriberPlanOutputDTO;
use Src\Domain\Entities\Enums\PaymentAction;
use Src\Domain\Exceptions\BusinessException;
use Src\Domain\Services\ContractService;
use Src\Infra\Eloquent\ContractModel;
use Src\Infra\Eloquent\PlanModel;

class SubscribePlanUseCase
{
    public function __construct(private ContractService $service) {}

    /**
     * @throws BusinessException
     */
    public function execute(SubscriberPlanInputDto $input): SubscriberPlanOutputDTO
    {
        $plan = PlanModel::findOrFail($input->planId);

        // regra: não pode ter contrato ativo
        $active = $this->service->getActivePlan($input->userId);
        if ($active) {
            throw new BusinessException('Usuário já possui um plano ativo.');
        }

        $now        = Carbon::now();
        $expiration = $this->service->computeNextMonthFrom($now);
        $windowFrom = $this->service->computeNextRenewalAllowed($expiration);

        // persistência via Eloquent
        $contract = ContractModel::create([
            'user_id'                    => $input->userId,
            'plan_id'                    => $plan->id,
            'started_at'                 => $now,
            'expiration_date'            => $expiration,
            'next_renewal_available_at'  => $windowFrom,
            'status'                     => 'active',
        ]);

        $payment = $contract->payments()->create([
            'type'        => 'pix',
            'plan_value'  => $plan->price,
            'price'       => $plan->price,
            'action'      => PaymentAction::purchase,
            'credit'      => 0,
            'payment_at'  => $now,
            'status'      => 'paid',
        ]);

        return new SubscriberPlanOutputDTO(
            $plan->toArray(),
            $payment->toArray()
        );
    }
}
