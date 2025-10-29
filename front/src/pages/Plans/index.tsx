import { api } from "../../lib/api";
import {useQuery} from "@tanstack/react-query";
import {useNavigate} from "react-router-dom";
import {ActivePlanResponse} from "@/types/Response.ts";
import {Plan} from "@/types/Entities.ts";

function currencyBRL(v: number) {
    return v.toLocaleString("pt-BR", { style: "currency", currency: "BRL" });
}

export default function PlansPage() {
    const navigate = useNavigate();
    const { data: plans, isLoading, error} = useQuery({
        queryKey: ["plans"],
        queryFn: (): Promise<Plan[]> => api.listPlans(),
    })

    const { data: activePlan, isLoading: LoadingActivePlan} = useQuery({
        queryKey: ["activePlan"],
        queryFn: (): Promise<ActivePlanResponse> => api.getActive(),
    })

    const activePlanId: number | null = activePlan?.plan?.id ?? null;

    function ctaLabel(planId: number): string {
        if (activePlanId === null) return "Comprar plano";
        if (activePlanId === planId) return "Renovar";
        return "Mudar de plano";
    }

    function handleCTA(planId: number): void {
        // Pré-transação será implementada depois
        navigate(`/order?plan_id=${planId}`);
    }

    if (isLoading || LoadingActivePlan) {
        return (
            <div className="rounded-xl bg-white p-6 shadow animate-pulse">
                <div className="h-4 w-40 bg-gray-200 rounded mb-3" />
                <div className="h-4 w-64 bg-gray-200 rounded" />
            </div>
        );
    }

    if (error) {
        return (
            <div className="rounded-lg border border-red-200 bg-red-50 p-4 text-red-700">
                Erro ao carregar planos.
            </div>
        );
    }

    return (
        <section className="space-y-6">
            <div>
                <h1 className="text-2xl font-bold text-gray-900">Planos</h1>
                <p className="text-gray-600">Escolha o plano ideal para você.</p>
            </div>

            <div className="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                {plans?.map((p) => {
                    const isActive = activePlanId === p.id;
                    return (
                        <article
                            key={p.id}
                            className="relative rounded-2xl bg-white shadow-lg overflow-hidden"
                        >
                            {/* Faixa laranja no topo */}
                            <div className="py-6 pr-20">
                                <div className="bg-orange-600 text-white px-6 py-4">
                                    <h3 className="text-lg font-semibold leading-tight">{p.description}</h3>
                                    <p className="text-sm opacity-90">/clientes ativos</p>
                                </div>
                            </div>

                            {/* Conteúdo */}
                            <div className="px-6 py-2 text-gray-800">
                                <div className="mb-4">
                                    <div className="text-sm font-semibold text-gray-600">Preço:</div>
                                    <div className="text-3xl font-extrabold">
                                        {currencyBRL(p.price)}
                                        <span className="text-base font-semibold text-gray-500"> /mês</span>
                                    </div>
                                </div>

                                <div>
                                    <div className="text-sm font-semibold text-gray-600">Armazenamento:</div>
                                    <div className="text-2xl font-bold">
                                        {(p.gigabytesStorage ?? 0).toLocaleString("pt-BR")} GB
                                    </div>
                                </div>

                                {isActive && (
                                    <div className="mt-3">
                    <span className="inline-block rounded-full bg-green-100 px-3 py-1 text-xs font-medium text-green-800">
                      Plano ativo
                    </span>
                                    </div>
                                )}
                            </div>

                            {/* CTA */}
                            <div className="px-6 pb-6">
                                <button
                                    onClick={() => handleCTA(p.id)}
                                    className={`w-full rounded-md px-4 py-2 text-sm font-medium
                    ${isActive ? "bg-black text-white" : "bg-[#F5BE01] text-black"} hover:opacity-90`}
                                >
                                    {ctaLabel(p.id)}
                                </button>
                            </div>
                        </article>
                    );
                })}
            </div>
        </section>
    );
}
