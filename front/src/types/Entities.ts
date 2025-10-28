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

type ContractStatus = string & ("active" | "inactive")

export interface Contract {
    id: number;
    status: ContractStatus;
    started_at?: string | null;
    expiration_date?: string | null;
}

export interface Payment {
    id: number;
    price: number;
    credit: number;
    payment_at: string;
    status: string;
}

export type ActionStatus =  string & ("purchase" | "renew" | "change_plan")