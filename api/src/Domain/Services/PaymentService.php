<?php

namespace Src\Domain\Services;

use Src\Infra\Eloquent\PaymentModel;
use Carbon\Carbon;

class PaymentService
{
    public function getPaymentHistory(int $userId): array
    {
        $payments = PaymentModel::query()
            ->select([
                'payments.payment_at',
                'contracts.expiration_date',
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
                'payment_at' => Carbon::parse($p->payment_at)->format('d/m/Y'),
                'expiration_date' => Carbon::parse($p->expiration_date)->format('d/m/Y'),
                'plan' => $p->plan,
                'type' => match ($p->action) {
                    'renewal' => 'renew',
                    default => 'purchase',
                },
                'payment_method' => strtoupper($p->type ?? 'PIX'),
                'price' => number_format($p->plan_value, 2, ',', '.'),
                'credit' => number_format($p->credit, 2, ',', '.'),
                'total' => number_format($p->price, 2, ',', '.'),
            ];
        })->toArray();
    }
}
