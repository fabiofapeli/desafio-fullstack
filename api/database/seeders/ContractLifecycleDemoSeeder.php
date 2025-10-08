<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Src\Infra\Eloquent\ContractModel;
use Src\Infra\Eloquent\PaymentModel;
use Src\Infra\Eloquent\PlanModel;
use Src\Infra\Eloquent\UserModel;

class ContractLifecycleDemoSeeder extends Seeder
{
    public function run(): void
    {
        // Usuário fixo (id=1) para casar com o front
        $user = UserModel::first();
        if (!$user) {
            $user = UserModel::factory()->create([
                'name'  => 'Usuário Demo',
                'email' => 'demo@inmediam.com.br',
            ]);
        }

        // Usar planos já existentes do PlanSeeder
        // 1: Individual (9,90) | 2: Até 10 (87,00) | 3: Até 25 (197,00) | 4: Até 50 (347,00)
        $plan25 = PlanModel::findOrFail(3); // 197,00
        $plan50 = PlanModel::findOrFail(4); // 347,00
        $plan10 = PlanModel::findOrFail(2); // 87,00

        // ---------- Helpers ----------
        $makeContract = function (int $userId, int $planId, string $start): ContractModel {
            return ContractModel::create([
                'user_id'         => $userId,
                'plan_id'         => $planId,
                'started_at'      => Carbon::parse($start),
                'expiration_date' => Carbon::parse($start)->copy()->addMonthNoOverflow(),
                'next_renewal_available_at' => null,
                'ended_at'        => null,
                'status'          => 'active',
            ]);
        };

        $pay = function (ContractModel $c, string $action, float $planValue, float $price, float $credit, string $date): void {
            // 'payment_type' e 'type' = 'pix' para satisfazer schemas legados / constraints
            PaymentModel::create([
                'contract_id'  => $c->id,
                'action'       => $action,    // 'purchase' | 'renewal'
                'type'         => 'pix',
                'plan_value'   => $planValue,
                'price'        => $price,
                'credit'       => $credit,
                'payment_at'   => Carbon::parse($date),
                'status'       => 'paid',
            ]);
        };

        // dias do mês segundo a REGRA DE NEGÓCIO (fev sempre 28)
        $fixedDaysInMonth = function (\Carbon\Carbon $anyDay): int {
            $m = (int)$anyDay->format('n');
            if ($m === 2) return 28;                          // ignora bissexto
            return in_array($m, [1,3,5,7,8,10,12], true) ? 31 : 30;
        };

        // crédito/preço na TROCA: usa o mês do ciclo (expiração - 1 mês)
        $quoteChange = function (float $oldPlanPrice, string $now, string $expires, float $newPlanPrice) use ($fixedDaysInMonth): array {
            $nowC = Carbon::parse($now);
            $expC = Carbon::parse($expires);

            // mês vigente do ciclo
            $cycleStart  = $expC->copy()->subMonthNoOverflow();
            $daysInCycle = $fixedDaysInMonth($cycleStart);

            // dias restantes/ usados no ciclo atual
            $daysRemaining = max(0, $nowC->diffInDays($expC, false));
            $daysUsed      = max(0, min($daysInCycle, $daysInCycle - $daysRemaining));

            // preço/dia TRUNCADO (duas casas)
            $daily = floor(($oldPlanPrice / $daysInCycle) * 100) / 100;

            // crédito pela sua fórmula: crédito = valor_plano - (preço_dia × dias_usados)
            $consumed = round($daily * $daysUsed, 2);
            $credit   = round(max(0, $oldPlanPrice - $consumed), 2);

            // valor a pagar no novo plano
            $price = round(max(0, $newPlanPrice - $credit), 2);

            return [$credit, $price];
        };

        // define renovação em data respeitando a janela: max(data_solicitada, expiration-5)
        $renewInWindow = function (ContractModel $c, float $planValue, string $date) use ($pay) {
            $newExp = Carbon::parse($c->expiration_date)->copy()->addMonthNoOverflow();
            $c->update(['expiration_date' => $newExp]);
            $pay($c, 'renewal', $planValue, $planValue, 0, $date);
        };

        // ---------- Timeline coerente ----------

        // 10/01/2024 - COMPRA Plano 25 (R$ 197)
        $c1 = $makeContract($user->id, $plan25->id, '2024-01-10');
        $pay($c1, 'purchase', $plan25->price, $plan25->price, 0, '2024-01-10');

        // 06/02/2024 - RENOVA Plano 25 (janela do venc. 10/02 abre 05/02)
        $renewInWindow($c1, $plan25->price, '2024-02-06'); // expiração passa a 06/03/2024

        // 05/03/2024 - TROCA para Plano 50
        // usa c1.expiration (06/03) p/ calcular crédito
        [$creditA, $priceA] = $quoteChange($plan25->price, '2024-03-05', $c1->expiration_date->toDateString(), $plan50->price);
        $c1->update(['status' => 'inactive', 'ended_at' => Carbon::parse('2024-03-05')]);
        $c2 = $makeContract($user->id, $plan50->id, '2024-03-05');
        $pay($c2, 'purchase', $plan50->price, $priceA, $creditA, '2024-03-05');

        // 03/04/2024 - TROCA para Plano 10
        [$creditB, $priceB] = $quoteChange($plan50->price, '2024-04-03', $c2->expiration_date->toDateString(), $plan10->price);
        $c2->update(['status' => 'inactive', 'ended_at' => Carbon::parse('2024-04-03')]);
        $c3 = $makeContract($user->id, $plan10->id, '2024-04-03');
        $pay($c3, 'purchase', $plan10->price, $priceB, $creditB, '2024-04-03');

        // 01/05/2024 - RENOVA Plano 10 (janela do venc. 03/05 abre 28/04)
        $renewInWindow($c3, $plan10->price, '2024-05-01'); // expiração → 01/06/2024

        // 01/06/2024 - RENOVA Plano 10 (janela do venc. 01/06 abre 27/05)
        $renewInWindow($c3, $plan10->price, '2024-06-01'); // expiração → 01/07/2024

        // 22/06/2024 - TROCA para Plano 25 (dentro do ciclo do plano 10)
        [$creditC, $priceC] = $quoteChange($plan10->price, '2024-06-22', $c3->expiration_date->toDateString(), $plan25->price);
        $c3->update(['status' => 'inactive', 'ended_at' => Carbon::parse('2024-06-22')]);
        $c4 = $makeContract($user->id, $plan25->id, '2024-06-22');
        $pay($c4, 'purchase', $plan25->price, $priceC, $creditC, '2024-06-22');

        // 17/07/2024 - RENOVA Plano 25 (venc. 22/07 → janela abre 17/07)
        $renewInWindow($c4, $plan25->price, '2024-07-17'); // expiração → 17/08/2024
    }
}
