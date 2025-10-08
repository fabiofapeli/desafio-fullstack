import { useEffect, useMemo, useState } from "react";
import { useLocation, useNavigate, Link } from "react-router-dom";
import { api, PreviewResponse } from "../../lib/api";

const fmtBRL = (v?: number | null) =>
    v == null
        ? "-"
        : v.toLocaleString("pt-BR", { style: "currency", currency: "BRL" });

const fmtDate = (s?: string | null) =>
    s ? new Date(s).toLocaleDateString("pt-BR") : "-";

function useQuery() {
    const { search } = useLocation();
    return useMemo(() => new URLSearchParams(search), [search]);
}

function isWithinWindow(window?: { available_from: string; expiration_date: string } | null) {
    if (!window?.available_from || !window?.expiration_date) return false;
    const now = new Date();
    const start = new Date(window.available_from);
    const end = new Date(window.expiration_date);
    // considera o fim do dia
    end.setHours(23, 59, 59, 999);
    return now >= start && now <= end;
}

export default function OrderPage() {
    const q = useQuery();
    const planId = Number(q.get("plan_id"));
    const navigate = useNavigate();

    const [data, setData] = useState<PreviewResponse | null>(null);
    const [loading, setLoading] = useState(true);
    const [err, setErr] = useState<string | null>(null);
    const [submitting, setSubmitting] = useState(false);
    const [result, setResult] = useState<any | null>(null);

    useEffect(() => {
        let alive = true;

        (async () => {
            if (!planId || Number.isNaN(planId)) {
                setErr("Plano inválido.");
                setLoading(false);
                return;
            }
            try {
                const r = await api.preview(planId);
                if (!alive) return;
                setData(r);
            } catch (e: any) {
                console.error(e);
                setErr(e?.data?.message ?? "Não foi possível carregar a prévia.");
            } finally {
                if (alive) setLoading(false);
            }
        })();

        return () => {
            alive = false;
        };
    }, [planId]);

    async function handleConfirm() {
        if (!data) return;
        setSubmitting(true);
        setErr(null);
        try {
            let r: any;
            if (data.action === "purchase") {
                r = await api.subscribe(data.plan.id);
                // ✅ compra: redireciona para Home
                navigate("/");
                return;
            } else if (data.action === "renew") {
                r = await api.renew();
            } else {
                r = await api.changePlan(data.plan.id);
                navigate("/");
                return;
            }
            setResult(r);
        } catch (e: any) {
            console.error(e);
            setErr(e?.data?.message ?? "Falha ao executar a transação.");
        } finally {
            setSubmitting(false);
        }
    }

    if (loading) {
        return (
            <div className="rounded-2xl bg-white p-6 shadow-md animate-pulse">
                <div className="h-4 w-48 bg-gray-200 rounded mb-3" />
                <div className="h-4 w-72 bg-gray-200 rounded" />
            </div>
        );
    }

    if (err) {
        return (
            <section className="space-y-4">
                <div className="rounded-lg border border-red-200 bg-red-50 p-4 text-red-700">{err}</div>
                <div className="flex gap-3">
                    <button onClick={() => navigate(-1)} className="rounded-md bg-black text-white px-4 py-2 text-sm">
                        Voltar
                    </button>
                    <Link to="/plans" className="rounded-md bg-[#F5BE01] text-black px-4 py-2 text-sm">
                        Ir para os planos
                    </Link>
                </div>
            </section>
        );
    }

    if (!data) return null;

    const { plan, action, renewal_window, credit, price } = data;
    const canRenew = action === "renew" ? isWithinWindow(renewal_window ?? null) : false;

    const title =
        action === "purchase"
            ? "Confirmar compra"
            : action === "renew"
                ? "Confirmar renovação"
                : "Confirmar mudança de plano";

    const confirmLabel =
        action === "purchase"
            ? "Confirmar compra"
            : action === "renew"
                ? "Confirmar renovação"
                : "Confirmar mudança";

    return (
        <section className="space-y-6">
            <header>
                <h1 className="text-2xl font-bold text-gray-900">{title}</h1>
                <p className="text-gray-600">Revise os detalhes antes de continuar.</p>
            </header>

            {/* Card: Plano */}
            <article className="overflow-hidden max-w-2xl rounded-3xl bg-white shadow-md ring-1 ring-black/5">

                <div className="py-6 pr-20">
                    <div className="bg-orange-600 px-6 py-4 text-white">
                        <h3 className="text-lg font-semibold leading-tight">{plan.description}</h3>
                        <p className="text-sm opacity-90">/clientes ativos</p>
                    </div>
                </div>

                <div className="p-6 text-gray-800 grid gap-6 sm:grid-cols-2">
                    <div>
                        <div className="text-sm font-semibold text-gray-600">Valor do plano</div>
                        <div className="mt-1 text-3xl font-extrabold">{fmtBRL(plan.price)}</div>
                    </div>
                    <div>
                        <div className="text-sm font-semibold text-gray-600">Armazenamento</div>
                        <div className="mt-1 text-2xl font-extrabold">
                            {(plan.gigabytesStorage ?? 0).toLocaleString("pt-BR")} GB
                        </div>
                    </div>
                </div>
            </article>

            {/* Card: Resumo */}
            <article className="rounded-3xl bg-white p-6 shadow-md ring-1 ring-black/5">
                <h3 className="mb-4 text-lg font-semibold text-gray-900">Resumo</h3>

                {action === "purchase" && (
                    <div className="text-gray-800">
                        <p>Você está contratando este plano agora.</p>
                        <p className="mt-2">
                            <strong>Total:</strong> {fmtBRL(plan.price)}
                        </p>
                    </div>
                )}

                {action === "renew" && (
                    <div className="text-gray-800 space-y-2">
                        {canRenew ? (
                            <>
                                <p>Você está renovando seu plano atual.</p>
                                <p>
                                    <strong>Janela de renovação:</strong>{" "}
                                    {fmtDate(renewal_window?.available_from)} até{" "}
                                    {fmtDate(renewal_window?.expiration_date)}
                                </p>
                                <p>
                                    <strong>Total:</strong> {fmtBRL(plan.price)}
                                </p>
                            </>
                        ) : (
                            <>
                                <p className="font-medium text-red-700">
                                    Renovação permitida apenas na janela de renovação.
                                </p>
                                {renewal_window && (
                                    <p className="text-sm text-gray-600">
                                        Próxima janela: {fmtDate(renewal_window.available_from)} até{" "}
                                        {fmtDate(renewal_window.expiration_date)}
                                    </p>
                                )}
                            </>
                        )}
                    </div>
                )}

                {action === "change_plan" && (
                    <div className="text-gray-800 space-y-2">
                        <p>Você está trocando de plano.</p>

                        {/* valores ao lado das labels */}
                        <div className="text-lg">
                            <div className="flex items-center gap-2">
                                <span className="text-gray-600">Crédito</span>
                                <span className="font-semibold text-blue-900">{fmtBRL(credit ?? 0)}</span>
                            </div>
                            <div className="flex items-center gap-2 mt-1">
                                <span className="text-gray-600">Valor a pagar</span>
                                <span className="font-semibold text-gray-900">
                  {fmtBRL(price ?? plan.price)}
                </span>
                            </div>
                        </div>
                    </div>
                )}

                {/* Ações */}
                <div className="mt-4 flex flex-wrap gap-3">
                    {/* Esconde botão confirmar quando for renew fora da janela */}
                    {!(action === "renew" && !canRenew) && (
                        <button
                            onClick={handleConfirm}
                            disabled={submitting}
                            className="inline-flex items-center justify-center rounded-md bg-[#F5BE01] px-5 py-2.5 text-sm font-medium text-black hover:opacity-90 disabled:opacity-60"
                        >
                            {submitting ? "Processando..." : confirmLabel}
                        </button>
                    )}

                    <button
                        onClick={() => navigate("/plans")}
                        className="rounded-md bg-black px-4 py-2 text-sm font-medium text-white hover:opacity-90"
                    >
                        Voltar aos planos
                    </button>
                </div>

                {/* Resultado (mostrado para renovação/troca; compra já redireciona) */}
                {result && (
                    <div className="mt-4 rounded-lg border border-green-200 bg-green-50 p-4 text-sm text-green-800">
                        Transação concluída! Você pode conferir em{" "}
                        <Link className="underline" to="/history">
                            Histórico
                        </Link>.
                    </div>
                )}
            </article>
        </section>
    );
}
