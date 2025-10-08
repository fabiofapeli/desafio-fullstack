import { useEffect, useState } from "react";
import { Link } from "react-router-dom";
import { api, ActivePlanResponse, User } from "@/lib/api.ts";

function currencyBRL(v?: number) {
    if (v == null) return "-";
    return v.toLocaleString("pt-BR", { style: "currency", currency: "BRL" });
}

function dateBR(d?: string | null) {
    if (!d) return "-";
    const dt = new Date(d);
    if (isNaN(dt.getTime())) return "-";
    return dt.toLocaleDateString("pt-BR");
}

export default function HomePage() {
    const [user, setUser] = useState<User | null>(null);
    const [active, setActive] = useState<ActivePlanResponse | null>(null);
    const [loading, setLoading] = useState(true);
    const [notFound, setNotFound] = useState(false);
    const [error, setError] = useState<string | null>(null);

    useEffect(() => {
        let isMounted = true;

        async function load() {
            try {
                const [u] = await Promise.all([api.getUser()]);
                if (!isMounted) return;
                setUser(u.user);

                try {
                    const a = await api.getActive();
                    if (!isMounted) return;
                    setActive(a);
                    setNotFound(false);
                } catch (err: any) {
                    if (!isMounted) return;
                    if (err?.status === 404) {
                        setNotFound(true);
                    } else {
                        setError("Não foi possível carregar o plano ativo.");
                        console.error(err);
                    }
                }
            } catch (err) {
                setError("Não foi possível carregar seus dados.");
                console.error(err);
            } finally {
                if (isMounted) setLoading(false);
            }
        }

        load();
        return () => { isMounted = false; };
    }, []);

    return (
        <div className="space-y-6">
            {/* saudação */}
            <div>
                <h1 className="text-2xl font-bold text-gray-900">
                    {user ? `Olá, ${user.name}!` : "Olá!"}
                </h1>
                <p className="text-gray-600">Bem-vindo ao painel InMediam.</p>
            </div>

            {/* estados de carregamento/erro */}
            {loading && (
                <div className="animate-pulse rounded-lg bg-white p-6 shadow">
                    <div className="h-4 w-40 bg-gray-200 rounded mb-3" />
                    <div className="h-4 w-64 bg-gray-200 rounded" />
                </div>
            )}

            {error && !loading && (
                <div className="rounded-lg border border-red-200 bg-red-50 p-4 text-red-700">
                    {error}
                </div>
            )}

            {/* sem plano ativo → card cute com CTA */}
            {!loading && !error && notFound && (
                <div className="rounded-2xl bg-gradient-to-br from-yellow-100 via-amber-100 to-yellow-200 border border-yellow-300/60 shadow p-6 flex items-center justify-between">
                    <div className="flex items-center gap-4">
                        <div className="h-12 w-12 rounded-full bg-[#F5BE01] text-black grid place-items-center text-xl font-extrabold">
                            ✨
                        </div>
                        <div>
                            <h3 className="text-lg font-semibold text-gray-900">
                                Você ainda não tem um plano ativo
                            </h3>
                            <p className="text-gray-700">
                                Conheça nossos planos e aproveite todos os benefícios.
                            </p>
                        </div>
                    </div>
                    <Link
                        to="/pre-transaction"
                        className="rounded-full bg-black text-white px-5 py-2 text-sm font-medium hover:opacity-90"
                    >
                        Ver planos
                    </Link>
                </div>
            )}

            {/* com plano ativo → exibir detalhes e CTA de renovação */}
            {!loading && !error && active && (
                <div className="grid gap-6 md:grid-cols-2">
                    <div className="rounded-xl bg-white p-6 shadow">
                        <h3 className="text-lg font-semibold text-gray-900 mb-4">
                            Seu Plano
                        </h3>
                        <dl className="grid grid-cols-2 gap-3 text-sm">
                            <dt className="text-gray-500">Descrição</dt>
                            <dd className="text-gray-900">{active.plan.description}</dd>

                            <dt className="text-gray-500">Preço</dt>
                            <dd className="text-gray-900">{currencyBRL(active.plan.price)}</dd>

                            <dt className="text-gray-500">Clientes</dt>
                            <dd className="text-gray-900">{active.plan.numberOfClients ?? "-"}</dd>

                            <dt className="text-gray-500">Armazenamento</dt>
                            <dd className="text-gray-900">
                                {active.plan.gigabytesStorage ? `${active.plan.gigabytesStorage} GB` : "-"}
                            </dd>
                        </dl>
                    </div>

                    <div className="rounded-xl bg-white p-6 shadow flex flex-col">
                        <h3 className="text-lg font-semibold text-gray-900 mb-4">
                            Seu Contrato
                        </h3>
                        <dl className="grid grid-cols-2 gap-3 text-sm flex-1">
                            <dt className="text-gray-500">Status</dt>
                            <dd className="text-gray-900 capitalize">{active.contract.status}</dd>

                            <dt className="text-gray-500">Início</dt>
                            <dd className="text-gray-900">{dateBR(active.contract.started_at)}</dd>

                            <dt className="text-gray-500">Vencimento</dt>
                            <dd className="text-gray-900">{dateBR(active.contract.expiration_date)}</dd>
                        </dl>

                        <div className="mt-4">
                            <Link
                                to={`/pre-transaction?plan_id=${active.plan.id}`}
                                className="inline-flex items-center justify-center rounded-md bg-[#F5BE01] px-4 py-2 text-black font-medium hover:opacity-90"
                            >
                                Renovar plano
                            </Link>
                        </div>
                    </div>
                </div>
            )}
        </div>
    );
}
