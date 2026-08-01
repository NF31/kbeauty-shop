import type { CartStoreItem } from '@/stores/cart-store';
import type { Auth } from '@/types/auth';

declare module 'react' {
    // eslint-disable-next-line @typescript-eslint/no-unused-vars
    interface InputHTMLAttributes<T> {
        passwordrules?: string;
    }
}

declare global {
    interface Window {
        dataLayer?: Record<string, unknown>[];
    }
}

declare module '@inertiajs/core' {
    export interface InertiaConfig {
        sharedPageProps: {
            name: string;
            appUrl: string;
            gtmId: string | null;
            auth: Auth;
            locale: string;
            sidebarOpen: boolean;
            cart: {
                items: CartStoreItem[];
                subtotalCents: number;
                totalCents: number;
                currency: string;
                itemCount: number;
            };
            honeypot: {
                enabled: boolean;
                nameFieldName: string;
                validFromFieldName: string;
                encryptedValidFrom: string;
            };
            [key: string]: unknown;
        };
    }
}
