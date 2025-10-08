// Pequeno helper pra centralizar chamadas HTTP
export const API_BASE =
    import.meta.env.VITE_API_BASE_URL ?? 'http://127.0.0.1:8000';

type HttpError = { status: number; data?: any };

async function getJSON<T>(path: string): Promise<T> {
    const res = await fetch(`${API_BASE}${path}`, {
        headers: { Accept: 'application/json' },
    });

    if (!res.ok) {
        let payload: undefined = undefined;
        try {
            payload = await res.json();
        } catch {}
        throw <HttpError>{ status: res.status, data: payload };
    }
    return (await res.json()) as T;
}

export interface User {
    id: number;
    name: string;
    email: string;
}

export interface ActivePlanResponse {
    contract: {
        id: number;
        status: 'active' | 'inactive';
        started_at?: string | null;
        expiration_date?: string | null;
    };
    plan: {
        id: number;
        description: string;
        numberOfClients?: number;
        gigabytesStorage?: number;
        price: number;
        active?: boolean;
    };
    payments: Array<{
        id: number;
        price: number;
        credit: number;
        payment_at: string;
        status: string;
    }>;
}

export const api = {
    getUser: () => getJSON<User>('/api/user'),
    getActive: () => getJSON<ActivePlanResponse>('/api/plans/active'),
};
