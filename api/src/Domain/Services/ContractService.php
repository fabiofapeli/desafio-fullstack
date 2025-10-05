<?php

namespace Src\Domain\Services;

use Carbon\Carbon;
use Src\Infra\Eloquent\ContractModel;

class ContractService
{
    /**
     * Retorna o contrato ativo de um usuário (ou null se não houver)
     */
    public function getActivePlan(int $userId): ?ContractModel
    {
        return ContractModel::where('user_id', $userId)
            ->where('status', 'active')
            ->where('expiration_date', '>', Carbon::now())
            ->first();
    }

    /**
     * Cria um novo contrato ativo e marca o pagamento como pago.
     */
    public function createNewContract(int $userId, int $planId): ContractModel
    {
        $now = Carbon::now();
        $expiration = $now->copy()->addMonth();

        $contract = ContractModel::create([
            'user_id' => $userId,
            'plan_id' => $planId,
            'started_at' => $now,
            'expiration_date' => $expiration,
            'status' => 'active',
        ]);

        $contract->payments()->create([
            'type' => 'pix',
            'price' => $contract->plan->price,
            'payment_at' => $now,
            'status' => 'paid',
        ]);

        return $contract->load(['plan', 'payments']);
    }
}

