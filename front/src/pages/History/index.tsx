import { useEffect, useState } from "react";
import { api, PaymentHistoryItem } from "../../lib/api";

export default function HistoryPage() {
    const [items, setItems] = useState<PaymentHistoryItem[]>([]);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState<string | null>(null);

    useEffect(() => {
        let alive = true;

        (async () => {
            try {
                const data = await api.getPaymentsHistory();
                if (!alive) return;
                setItems(data);
            } catch (err) {
                console.error(err);
                setError("Não foi possível carregar o histórico.");
            } finally {
                if (alive) setLoading(false);
            }
        })();

        return () => { alive = false; };
    }, []);

    return (
        <section className="space-y-6">
            <div>
                <h1 className="text-2xl font-bold text-gray-900">Histórico de pagamentos</h1>
                <p className="text-gray-600">Veja suas compras, renovações e trocas de plano.</p>
            </div>

            {loading && (
                <div className="rounded-lg bg-white p-6 shadow animate-pulse">
                    <div className="h-4 w-40 bg-gray-200 rounded mb-3" />
                    <div className="h-4 w-full bg-gray-200 rounded mb-2" />
                    <div className="h-4 w-4/5 bg-gray-200 rounded" />
                </div>
            )}

            {error && !loading && (
                <div className="rounded-lg border border-red-200 bg-red-50 p-4 text-red-700">
                    {error}
                </div>
            )}

            {!loading && !error && (
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
                                        <td>{it.data_pagamento}</td>
                                        <td>{it.plano}</td>
                                        <td>{it.tipo}</td>
                                        <td>{it.forma_pagamento}</td>
                                        <td>R$ {it.valor_plano}</td>
                                        <td>R$ {it.credito}</td>
                                        <td className="font-semibold">R$ {it.valor_pago}</td>
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
