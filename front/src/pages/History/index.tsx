import { api } from "../../lib/api";
import { PaymentHistoryItem } from "@/types/Response";
import {paymentTypeTranslation} from "@/utils/translations.ts";
import {useQuery} from "@tanstack/react-query";

export default function HistoryPage() {

    const { data, isLoading, error} = useQuery({
        queryKey: ["paymentsHistory"],
        queryFn: (): Promise<PaymentHistoryItem[]> => api.getPaymentsHistory(),
    })

    const items: PaymentHistoryItem[] = data ?? [];

    return (
        <section className="space-y-6">
            <div>
                <h1 className="text-2xl font-bold text-gray-900">Histórico de pagamentos</h1>
                <p className="text-gray-600">Veja suas compras, renovações e trocas de plano.</p>
            </div>

            {isLoading && (
                <div className="rounded-lg bg-white p-6 shadow animate-pulse">
                    <div className="h-4 w-40 bg-gray-200 rounded mb-3" />
                    <div className="h-4 w-full bg-gray-200 rounded mb-2" />
                    <div className="h-4 w-4/5 bg-gray-200 rounded" />
                </div>
            )}

            {error && !isLoading && (
                <div className="rounded-lg border border-red-200 bg-red-50 p-4 text-red-700">
                    Erro ao carregar dados
                </div>
            )}

            {!isLoading && !error && (
                <>
                    {items.length === 0 ? (
                        <div className="rounded-lg bg-white p-6 shadow">
                            <p className="text-gray-700">Você ainda não possui pagamentos.</p>
                        </div>
                    ) : (
                        <div className="overflow-x-auto rounded-lg bg-white shadow">
                            <table className="min-w-full text-sm">
                                <thead className="bg-gray-50 text-gray-700">
                                <tr className="[&>th]:py-3 [&>th]:px-4 text-left">
                                    <th>Data pagamento</th>
                                    <th>Plano</th>
                                    <th>Tipo</th>
                                    <th>Forma de pagamento</th>
                                    <th>Valor do plano</th>
                                    <th>Crédito</th>
                                    <th>Valor pago</th>
                                </tr>
                                </thead>
                                <tbody className="divide-y divide-gray-100 text-gray-800">
                                {items.map((it, idx) => (
                                    <tr key={idx} className="[&>td]:py-3 [&>td]:px-4">
                                        <td>{it.payment_at}</td>
                                        <td>{it.plan}</td>
                                        <td>{paymentTypeTranslation[it.type] ?? "—"}</td>
                                        <td>{it.payment_method}</td>
                                        <td>R$ {it.price}</td>
                                        <td>R$ {it.credit}</td>
                                        <td className="font-semibold">R$ {it.total}</td>
                                    </tr>
                                ))}
                                </tbody>
                            </table>
                        </div>
                    )}
                </>
            )}
        </section>
    );
}
