<?php

namespace Src\Domain\Services;

use Src\Infra\Eloquent\PaymentModel;

class PaymentService
{
    public function getPaymentHistory(int $userId): array
    {
        $payments = PaymentModel::query()
            ->select([
                'payments.payment_at',
                'plans.description as plan',
                'payments.action',
                'payments.type',
                'payments.plan_value',
                'payments.credit',
                'payments.price',
            ])
            ->join('contracts', 'contracts.id', '=', 'payments.contract_id')
            ->join('plans', 'plans.id', '=', 'contracts.plan_id')
            ->where('contracts.user_id', $userId)
            ->orderByDesc('payments.payment_at')
            ->get();

        return $payments->map(function ($p) {
            return [
                'data_pagamento' => $p->payment_at->format('d/m/Y'),
                'plano' => $p->plan,
                'tipo' => match ($p->action) {
                    'renewal' => 'Renovação',
                    default => 'Compra',
                },
                'forma_pagamento' => strtoupper($p->type ?? 'PIX'),
                'valor_plano' => number_format($p->plan_value, 2, ',', '.'),
                'credito' => number_format($p->credit, 2, ',', '.'),
                'valor_pago' => number_format($p->price, 2, ',', '.'),
            ];
        })->toArray();
    }
}
