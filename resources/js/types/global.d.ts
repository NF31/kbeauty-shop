import type { CartStoreItem } from '@/stores/cart-store';
import type { Auth } from '@/types/auth';

declare module 'react' {
    // eslint-disable-next-line @typescript-eslint/no-unused-vars
    interface InputHTMLAttributes<T> {
        passwordrules?: string;
    }
}

declare module '@inertiajs/core' {
    export interface InertiaConfig {
        sharedPageProps: {
            name: string;
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
            [key: string]: unknown;
        };
    }
}
