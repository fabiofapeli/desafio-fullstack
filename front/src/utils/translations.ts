import {ActionStatus} from "@/types/Entities.ts";

export const paymentTypeTranslation: Record<ActionStatus, string> = {
    purchase: "Compra",
    renew: "Renovação",
    change_plan: "Troca de plano",
};
