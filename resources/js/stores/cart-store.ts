import { create } from 'zustand';

export type CartStoreItem = {
    id: number;
    productName: string;
    productSlug: string;
    sku: string;
    quantity: number;
    unitPriceCents: number;
    lineTotalCents: number;
    stockQuantity: number;
    thumbnailUrl: string | null;
};

type CartSnapshot = {
    items: CartStoreItem[];
    subtotalCents: number;
    totalCents: number;
    currency: string;
};

type CartState = CartSnapshot & {
    itemCount: number;
    /** Resynchronise le store avec la réponse Inertia authoritative (source de vérité). */
    sync: (cart: CartSnapshot) => void;
    /** Met à jour l'UI immédiatement, avant la réponse serveur — resynchronisée ensuite via sync(). */
    setQuantityOptimistic: (itemId: number, quantity: number) => void;
    removeOptimistic: (itemId: number) => void;
};

function countItems(items: CartStoreItem[]): number {
    return items.reduce((sum, item) => sum + item.quantity, 0);
}

export const useCartStore = create<CartState>((set) => ({
    items: [],
    subtotalCents: 0,
    totalCents: 0,
    currency: 'EUR',
    itemCount: 0,
    sync: (cart) => set({ ...cart, itemCount: countItems(cart.items) }),
    setQuantityOptimistic: (itemId, quantity) =>
        set((state) => {
            const items = state.items.map((item) =>
                item.id === itemId
                    ? {
                          ...item,
                          quantity,
                          lineTotalCents: item.unitPriceCents * quantity,
                      }
                    : item,
            );

            return { items, itemCount: countItems(items) };
        }),
    removeOptimistic: (itemId) =>
        set((state) => {
            const items = state.items.filter((item) => item.id !== itemId);

            return { items, itemCount: countItems(items) };
        }),
}));
