<?php

namespace Src\Domain\Services;

use Carbon\Carbon;
use Src\Domain\Entities\Enums\RenewalPolicy;
use Src\Infra\Eloquent\ContractModel;

class ContractService
{
    /**
     * (opcional) leitura: ajuda a recuperar o contrato ativo.
     * Não altera estado; mantém o service stateless.
     */
    public function getActivePlan(int $userId): ?ContractModel
    {
        return ContractModel::where('user_id', $userId)
            ->where('status', 'active')
            ->where('expiration_date', '>=', Carbon::now())
            ->first();
    }

    /** Soma +1 mês a partir de uma data base (agora ou data de expiração) */
    public function computeNextMonthFrom(Carbon $from): Carbon
    {
        return $from->copy()->addMonth();
    }

    /** Data a partir da qual a renovação fica liberada (5 dias antes do vencimento) */
    public function computeNextRenewalAllowed(Carbon $expiration): Carbon
    {
        return $expiration->copy()
            ->subDays(RenewalPolicy::DAYS_BEFORE_EXPIRATION_ALLOWED);
    }

    /**
     * Janela de renovação (início e fim).
     * Se o contrato tiver next_renewal_available_at, usa; senão, calcula.
     * @return array{available_from:string, expiration_date:string}
     */
    public function getRenewalWindow(ContractModel $active): array
    {
        $expiration = Carbon::parse($active->expiration_date);
        $availableFrom = $active->next_renewal_available_at
            ? Carbon::parse($active->next_renewal_available_at)
            : $this->computeNextRenewalAllowed($expiration);

        return [
            'available_from'  => $availableFrom->toDateString(),
            'expiration_date' => $expiration->toDateString(),
        ];
    }

    /**
     * Checa se a renovação pode ser feita agora.
     * @return array{allowed:bool, reason?:string}
     */
    public function checkRenewalAllowed(ContractModel $active, ?Carbon $now = null): array
    {
        $now = $now ?: Carbon::now();
        $expiration = Carbon::parse($active->expiration_date);
        $availableFrom = $active->next_renewal_available_at
            ? Carbon::parse($active->next_renewal_available_at)
            : $this->computeNextRenewalAllowed($expiration);

        if ($now->lt($availableFrom)) {
            return [
                'allowed' => false,
                'reason'  => sprintf(
                    'Renovação permitida apenas a partir de %s.',
                    $availableFrom->toDateTimeString()
                ),
            ];
        }

        if ($now->gt($expiration)) {
            return [
                'allowed' => false,
                'reason'  => 'Contrato expirado.',
            ];
        }

        return ['allowed' => true];
    }

    /**
     * Cálculo do orçamento para TROCA de plano.
     * Crédito = preço-dia do plano atual * dias restantes do ciclo.
     * @return array{credit: float, price: float, days_remaining:int, daily_old: float}
     */
    public function quoteChangePlan(
        float $oldPlanPrice,
        float $newPlanPrice,
        Carbon $expirationDate,
        ?Carbon $now = null
    ): array {
        $now = $now ?: Carbon::now();

        // dias restantes no ciclo atual
        $daysRemaining = max(0, $now->diffInDays($expirationDate, false));

        // dias do ciclo atual (começa 1 mês antes da data de expiração)
        $cycleStart   = $expirationDate->copy()->subMonthNoOverflow();
        $daysInCycle  = $cycleStart->daysInMonth;

        $dailyOld = $daysInCycle > 0 ? $oldPlanPrice / $daysInCycle : 0.0;
        $credit   = round($dailyOld * $daysRemaining, 2);
        $price    = round(max(0, $newPlanPrice - $credit), 2);

        return [
            'credit'         => $credit,
            'price'          => $price,
            'days_remaining' => $daysRemaining,
            'daily_old'      => $dailyOld,
        ];
    }
}
