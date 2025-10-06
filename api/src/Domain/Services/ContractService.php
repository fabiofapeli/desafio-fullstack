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
            'status' => 'active',
        ]);

        $payment = $contract->payments()->create([
            'type' => 'pix',
            'price' => $contract->plan->price,
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
        $activeContract = $this->getActivePlan($input->userId);

        if (!$activeContract) {
            throw new BusinessException('Usuário não possui contrato ativo para renovação.');
        }

        // Atualiza data de expiração (+1 mês)
        $activeContract->expiration_date = Carbon::parse($activeContract->expiration_date)->addMonth();
        $activeContract->save();

        // Cria novo pagamento
        $payment = $activeContract->payments()->create([
            'type' => 'pix',
            'price' => $activeContract->plan->price,
            'payment_at' => Carbon::now(),
            'status' => 'paid',
        ]);

        return new RenewContractOutputDto(
            $activeContract->toArray(),
            $payment->toArray()
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

        // Calcula diferença real de dias (incluindo fração de horas)
        $now = Carbon::now();
        $expirationDate = Carbon::parse($activeContract->expiration_date);
        $daysRemaining = max(0, $now->floatDiffInDays($expirationDate, false));

        // Recupera planos antigo e novo
        $oldPlan = PlanModel::find($activeContract->plan_id);
        $newPlan = PlanModel::findOrFail($input->newPlanId);

        // Valor diário de cada plano
        $oldDaily = $oldPlan->price / 30;
        $newDaily = $newPlan->price / 30;

        // Converte crédito remanescente em dias no novo plano
        $creditValue = $daysRemaining * $oldDaily;
        $extraDays = $creditValue / $newDaily;

        // Nova data de expiração: +1 mês + dias extras (fração incluída)
        $expiration = $now->copy()->addMonth()->addDays(round($extraDays));

        // Inativar o contrato anterior
        $activeContract->update(['status' => ContractStatus::INACTIVE]);

        // Criar o novo contrato com o novo plano
        $newContract = ContractModel::create([
            'user_id' => $input->userId,
            'plan_id' => $input->newPlanId,
            'started_at' => $now,
            'expiration_date' => $expiration,
            'status' => ContractStatus::ACTIVE,
        ]);

        // Criar novo pagamento
        $payment = $newContract->payments()->create([
            'type' => 'pix',
            'price' => $newPlan->price,
            'payment_at' => $now,
            'status' => 'paid',
        ]);

        return new ChangePlanOutputDto(
            $newContract->load('plan')->toArray(),
            $payment->toArray()
        );
    }
}
