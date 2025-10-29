// src/lib/api.ts

// Base da API
import {Contract, Payment, Plan} from "@/types/Entities.ts";
import {ActivePlanResponse, PaymentHistoryItem, PreviewResponse, UserLoggedResponse} from "@/types/Response.ts";

export const API_BASE =
    import.meta.env.VITE_API_BASE_URL ?? "http://127.0.0.1:8000";

type HttpError = { status: number; data?: any };

/** Normaliza payloads que venham como {data: ...} ou {plans: [...]} */
function unwrap<T>(payload: any): T {
    if (payload && typeof payload === "object") {
        if ("data" in payload) return payload.data as T;
        if ("plans" in payload) return payload.plans as T;
    }
    return payload as T;
}

/** Requisição genérica (GET/POST/etc) com parse seguro do body */
async function request<T>(path: string, init?: RequestInit): Promise<T> {
    const res = await fetch(`${API_BASE}${path}`, {
        headers: { Accept: "application/json", ...(init?.headers || {}) },
        ...init,
    });

    // Lê o body uma vez
    const text = await res.text();
    let body: any = null;
    if (text) {
        try {
            body = JSON.parse(text);
        } catch {
            body = text; // caso seja string vazia ou não-JSON
        }
    }

    if (!res.ok) {
        throw <HttpError>{ status: res.status, data: body };
    }
    return unwrap<T>(body);
}

/** GET simples (aqui só delega pro request) */
const getJSON = <T,>(path: string) => request<T>(path);

/* ===== Endpoints normalizados ===== */
export const api = {
    getUser: (): Promise<UserLoggedResponse> => getJSON<UserLoggedResponse>("/api/user"),

    getActive: (): Promise<ActivePlanResponse> => getJSON<ActivePlanResponse>("/api/plans/active"),

    listPlans: (): Promise<Plan[]> => getJSON<Plan[]>("/api/plans"),

    getPaymentsHistory: (): Promise<PaymentHistoryItem[]> => getJSON<PaymentHistoryItem[]>("/api/payments/history"),

    preview: (planId: number): Promise<PreviewResponse> =>
        getJSON<PreviewResponse>(`/api/contracts/preview?plan_id=${planId}`),

    subscribe: (planId: number) =>
        request<{ plan: Plan; payment: Payment }>("/api/contracts", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ plan_id: planId }),
        }),

    renew: () =>
        request<{ contract: Contract; payment: Payment }>("/api/contracts/renew", {
            method: "POST",
        }),

    changePlan: (newPlanId: number) =>
        request<{ contract: Contract; payment: Payment }>("/api/contracts/change-plan", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ new_plan_id: newPlanId }),
        }),
};
