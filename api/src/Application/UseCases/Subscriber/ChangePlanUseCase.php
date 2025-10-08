<?php

namespace Src\Application\UseCases\Subscriber;

use Carbon\Carbon;
use Src\Application\UseCases\DTO\Subscriber\ChangePlanInputDto;
use Src\Application\UseCases\DTO\Subscriber\ChangePlanOutputDto;
use Src\Domain\Entities\Enums\ContractStatus;
use Src\Domain\Entities\Enums\PaymentAction;
use Src\Domain\Exceptions\BusinessException;
use Src\Domain\Services\ContractService;
use Src\Infra\Eloquent\ContractModel;
use Src\Infra\Eloquent\PlanModel;

class ChangePlanUseCase
{
    public function __construct(private ContractService $service) {}

    /**
     * @throws BusinessException
     */
    public function execute(ChangePlanInputDto $input): ChangePlanOutputDto
    {
        $active = $this->service->getActivePlan($input->userId);
        if (!$active) {
            throw new BusinessException('Usuário não possui contrato ativo para mudança de plano.');
        }

        $newPlan = PlanModel::findOrFail($input->newPlanId);
        $active->load('plan');

        // orçamento (crédito e preço final) via regras do service
        $quote = $this->service->quoteChangePlan(
            oldPlanPrice: $active->plan->price,
            newPlanPrice: $newPlan->price,
            expirationDate: Carbon::parse($active->expiration_date),
            now: Carbon::now()
        );

        // inativa o contrato atual
        $active->status = ContractStatus::INACTIVE;
        $active->save();

        // cria o novo contrato
        $now        = Carbon::now();
        $expiration = $this->service->computeNextMonthFrom($now);
        $windowFrom = $this->service->computeNextRenewalAllowed($expiration);

        $newContract = ContractModel::create([
            'user_id'                    => $input->userId,
            'plan_id'                    => $newPlan->id,
            'started_at'                 => $now,
            'expiration_date'            => $expiration,
            'next_renewal_available_at'  => $windowFrom,
            'status'                     => ContractStatus::ACTIVE,
        ]);

        // registra pagamento (com crédito aplicado)
        $payment = $newContract->payments()->create([
            'type'        => 'pix',
            'plan_value'  => $newPlan->price,
            'price'       => $quote['price'],
            'action'      => PaymentAction::purchase,
            'credit'      => $quote['credit'],
            'payment_at'  => $now,
            'status'      => 'paid',
        ]);

        return new ChangePlanOutputDto(
            contract: $newContract->load('plan')->toArray(),
            payment : $payment->toArray()
        );
    }
}
