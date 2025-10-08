// src/lib/api.ts

// Base da API
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

/* ===== Tipos ===== */
export interface User {
    id: number;
    name: string;
    email: string;
}

export interface Plan {
    id: number;
    description: string;
    numberOfClients?: number;
    gigabytesStorage?: number;
    price: number;
    active?: boolean;
}

export interface ActivePlanResponse {
    contract: {
        id: number;
        status: "active" | "inactive";
        started_at?: string | null;
        expiration_date?: string | null;
    };
    plan: Plan;
    payments: Array<{
        id: number;
        price: number;
        credit: number;
        payment_at: string;
        status: string;
    }>;
}

export interface PaymentHistoryItem {
    data_pagamento: string;
    data_expiracao: string;
    plano: string;
    tipo: string;            // "Compra" | "Renovação"
    forma_pagamento: string; // "PIX"
    valor_plano: string;     // "100,00"
    credito: string;         // "0,00"
    valor_pago: string;      // "100,00"
}

export interface PreviewResponse {
    plan: Plan;
    action: "purchase" | "renew" | "change_plan";
    renewal_window?: { available_from: string; expiration_date: string } | null;
    credit?: number | null; // para change_plan
    price?: number | null;  // para change_plan
}

/* ===== Endpoints normalizados ===== */
export const api = {
    getUser: () => getJSON<User>("/api/user"),

    getActive: () => getJSON<ActivePlanResponse>("/api/plans/active"),

    listPlans: () => getJSON<Plan[]>("/api/plans"),

    getPaymentsHistory: () => getJSON<PaymentHistoryItem[]>("/api/payments/history"),

    preview: (planId: number) =>
        getJSON<PreviewResponse>(`/api/contracts/preview?plan_id=${planId}`),

    subscribe: (planId: number) =>
        request<{ plan: Plan; payment: any }>("/api/contracts", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ plan_id: planId }),
        }),

    renew: () =>
        request<{ contract: any; payment: any }>("/api/contracts/renew", {
            method: "POST",
        }),

    changePlan: (newPlanId: number) =>
        request<{ contract: any; payment: any }>("/api/contracts/change-plan", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ new_plan_id: newPlanId }),
        }),
};
