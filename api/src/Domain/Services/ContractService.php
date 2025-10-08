<?php

namespace Src\Domain\Services;

use Carbon\Carbon;
use Src\Application\UseCases\DTO\Subscriber\ChangePlanInputDto;
use Src\Application\UseCases\DTO\Subscriber\ChangePlanOutputDto;
use Src\Application\UseCases\DTO\Subscriber\NewContractInputDto;
use Src\Application\UseCases\DTO\Subscriber\NewContractOutputDto;
use Src\Application\UseCases\DTO\Subscriber\RenewContractInputDto;
use Src\Application\UseCases\DTO\Subscriber\RenewContractOutputDto;
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

        $cycleStart = Carbon::parse($activeContract->expiration_date)->subMonthNoOverflow();
        $daysInCycle = $cycleStart->daysInMonth; // ou 28 fixo se quiser política comercial
        $daysRemaining = max(0, $now->diffInDays(Carbon::parse($activeContract->expiration_date), false));
        $dailyRateOld = $oldPlan->price / $daysInCycle;
        $credit = round($dailyRateOld * $daysRemaining, 2);

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

    /**
     * Retorna se o usuário possui contrato ativo e ainda válido (não expirado).
     */
    public function hasValidActiveContract(int $userId, ?Carbon $now = null): bool
    {
        $now = $now ?: Carbon::now();
        $active = $this->getActivePlan($userId);

        if (!$active || $active->status !== 'active') {
            return false;
        }

        // Se tiver expiration_date e já passou, considera inválido
        if ($active->expiration_date && $now->gt(Carbon::parse($active->expiration_date))) {
            return false;
        }

        return true;
    }

    /**
     * Calcula a janela de renovação com base no contrato ativo.
     * - Se existir next_renewal_available_at, usa ele;
     * - Caso contrário, usa a regra: 5 dias antes do vencimento.
     *
     * @return array{available_from: string, expiration_date: string}
     */
    public function getRenewalWindow(ContractModel $active): array
    {
        $expiration = Carbon::parse($active->expiration_date);
        $availableFrom = $active->next_renewal_available_at
            ? Carbon::parse($active->next_renewal_available_at)
            : $expiration->copy()->subDays(RenewalPolicy::DAYS_BEFORE_EXPIRATION_ALLOWED);

        return [
            'available_from'  => $availableFrom->toDateString(),
            'expiration_date' => $expiration->toDateString(),
        ];
    }

    /**
     * Retorna o "orçamento" para troca de plano: crédito e preço final.
     * Crédito = (dias restantes * preço diário do plano atual), onde preço diário = preço_mensal / dias_do_mês_corrente.
     *
     * @return array{credit: float, price: float}
     */
    public function getChangePlanQuote(ContractModel $active, PlanModel $newPlan, ?Carbon $now = null): array
    {
        $now = $now ?: Carbon::now();

        // contrato ativo deve ter relação.plan carregada
        if (!$active->relationLoaded('plan')) {
            $active->load('plan');
        }

        $expiration = Carbon::parse($active->expiration_date);
        // diffInDays(false) pode retornar negativo; garante mínimo 0
        $daysRemaining = max(0, $now->diffInDays($expiration, false));

        $daysInMonth = $now->daysInMonth ?: 30; // fallback seguro
        $oldDaily = $active->plan ? ($active->plan->price / $daysInMonth) : 0.0;

        $credit = round($oldDaily * $daysRemaining, 2);
        $price  = round(max(0, $newPlan->price - $credit), 2);

        return ['credit' => $credit, 'price' => $price];
    }
}
