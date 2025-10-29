import {ActionStatus, Contract, Payment, Plan, User} from "@/types/Entities.ts";

export interface ActivePlanResponse {
    contract: Contract;
    plan: Plan;
    payments: Payment[];
}

export interface UserLoggedResponse {
    user: User;
}

export interface PaymentHistoryItem {
    payment_at: string;
    expiration_date: string;
    plan: string;
    type: ActionStatus;      // "purchase" | "renew" | "change_plan"
    payment_method: string; // "PIX"
    price: string;     // "100,00"
    credit: string;         // "0,00"
    total: string;      // "100,00"
}

export interface PreviewResponse {
    plan: Plan;
    action: ActionStatus;
    renewal_window?: { available_from: string; expiration_date: string } | null;
    credit?: number | null; // para change_plan
    price?: number | null;  // para change_plan
}