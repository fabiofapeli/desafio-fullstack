<?php

namespace Src\Domain\Services;

use Carbon\Carbon;
use Src\Application\UseCases\DTO\Contract\NewContractInputDto;
use Src\Application\UseCases\DTO\Contract\RenewContractInputDto;
use Src\Application\UseCases\DTO\Contract\NewContractOutputDto;
use Src\Application\UseCases\DTO\Contract\RenewContractOutputDto;
use Src\Application\UseCases\DTO\Subscriber\ChangePlanInputDto;
use Src\Application\UseCases\DTO\Subscriber\ChangePlanOutputDto;
use Src\Domain\Entities\Enums\ContractStatus;
use Src\Domain\Entities\Enums\PaymentAction;
use Src\Domain\Entities\Enums\RenewalPolicy;
use Src\Domain\Exceptions\BusinessException;
use Src\Infra\Eloquent\ContractModel;
use Src\Infra\Eloquent\PlanModel;

class ContractService
{
    /**
     * Retorna o contrato ativo de um usuário (ou null se não houver)
     */
    public function getActivePlan(int $userId): ?ContractModel
    {
        return ContractModel::where('user_id', $userId)
            ->where('status', 'active')
            ->where('expiration_date', '>=', Carbon::now())
            ->first();
    }

    /**
     * Cria um novo contrato ativo e marca o pagamento como pago.
     */
    public function createNewContract(NewContractInputDto $input): NewContractOutputDto
    {
        $activeContract = $this->getActivePlan($input->userId);

        if ($activeContract) {
            throw new BusinessException('Usuário já possui um plano ativo.');
        }

        $now = Carbon::now();
        $expiration = $now->copy()->addMonth();

        $contract = ContractModel::create([
            'user_id' => $input->userId,
            'plan_id' => $input->planId,
            'started_at' => $now,
            'expiration_date' => $expiration,
            'next_renewal_available_at' => $this->computeNextRenewalAllowed($expiration),
            'status' => 'active',
        ]);

        $payment = $contract->payments()->create([
            'type' => 'pix',
            'price' => $contract->plan->price,
            'plan_value' => $contract->plan->price,
            'action' => PaymentAction::purchase,
            'credit' => 0,
            'payment_at' => $now,
            'status' => 'paid',
        ]);

        return new NewContractOutputDto(
            $contract->toArray(),
            $payment->toArray()
        );
    }

    public function renewContract(RenewContractInputDto $input): RenewContractOutputDto
    {
        $active = $this->getActivePlan($input->userId);
        if (!$active) {
            throw new BusinessException('Usuário não possui contrato ativo para renovação.');
        }

        $now = Carbon::now();

        // 1) Checagem por janela usando o timestamp
        if ($active->next_renewal_available_at && $now->lt($active->next_renewal_available_at)) {
            $msg = sprintf(
                'Renovação permitida apenas a partir de %s.',
                $active->next_renewal_available_at->toDateTimeString()
            );
            throw new BusinessException($msg);
        }

        // 2) Ainda que o timestamp não esteja populado, respeite a política (fallback)
        $daysRemaining = $now->diffInDays(Carbon::parse($active->expiration_date), false);
        if ($daysRemaining > RenewalPolicy::DAYS_BEFORE_EXPIRATION_ALLOWED) {
            throw new BusinessException(
                sprintf(
                    'Renovação permitida apenas a %d dias do vencimento (faltam %d dias).',
                    RenewalPolicy::DAYS_BEFORE_EXPIRATION_ALLOWED,
                    $daysRemaining
                )
            );
        }

        // 3) Renovar (+1 mês) e recalcular janela
        $active->expiration_date = Carbon::parse($active->expiration_date)->addMonth();
        $active->next_renewal_available_at = $this->computeNextRenewalAllowed($active->expiration_date);
        $active->save();

        $payment = $active->payments()->create([
            'type' => 'pix',
            'price' => $active->plan->price,
            'plan_value' => $active->plan->price,
            'action' => PaymentAction::renewal,
            'credit' => 0,
            'payment_at' => $now,
            'status' => 'paid',
        ]);

        return new RenewContractOutputDto(
            $active->toArray(),
            array_merge($payment->toArray(), ['credit' => $payment->credit ?? 0])
        );
    }


    /**
     * Mudança de plano:
     * - Contrato atual deve estar ativo.
     * - Status do contrato anterior vai para 'inactive'.
     * - Novo contrato soma 1 mês + dias de crédito convertidos pelo preço diário.
     */
    public function changePlan(ChangePlanInputDto $input): ChangePlanOutputDto
    {
        $now = Carbon::now();

        $activeContract = $this->getActivePlan($input->userId);

        if (!$activeContract) {
            throw new BusinessException('Usuário não possui contrato ativo para mudança de plano.');
        }

        $oldPlan = PlanModel::findOrFail($activeContract->plan_id);
        $newPlan = PlanModel::findOrFail($input->newPlanId);

        // cálculo de crédito proporcional
        $daysInMonth = 30;
        $daysUsed = $activeContract->started_at->diffInDays($now);
        $daysRemaining = max($daysInMonth - $daysUsed, 0);

        $dailyRateOld = $oldPlan->price / $daysInMonth;
        $credit = round($daysRemaining * $dailyRateOld, 2);

        // valor do novo pagamento = novo plano - crédito
        $amountToPay = max($newPlan->price - $credit, 0);

        // inativar contrato antigo
        $activeContract->update(['status' => ContractStatus::INACTIVE]);

        // criar novo contrato
        $newContract = ContractModel::create([
            'user_id' => $input->userId,
            'plan_id' => $newPlan->id,
            'started_at' => $now,
            'expiration_date' => $now->copy()->addMonth(),
            'next_renewal_available_at' => $this->computeNextRenewalAllowed($now->copy()->addMonth()),
            'status' => ContractStatus::ACTIVE,
        ]);

        // registrar pagamento com crédito aplicado
        $payment = $newContract->payments()->create([
            'type' => 'pix',
            'price' => $amountToPay,
            'plan_value' => $newPlan->price,
            'action' => PaymentAction::purchase,
            'credit' => $credit,
            'payment_at' => $now,
            'status' => 'paid',
        ]);

        return new ChangePlanOutputDto(
            $newContract->load('plan')->toArray(),
            $payment->toArray()
        );
    }

    private function computeNextRenewalAllowed(Carbon $expiration): Carbon
    {
        return $expiration->copy()->subDays(RenewalPolicy::DAYS_BEFORE_EXPIRATION_ALLOWED);
    }
}
